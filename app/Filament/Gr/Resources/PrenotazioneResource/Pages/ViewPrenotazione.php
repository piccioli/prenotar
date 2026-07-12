<?php

declare(strict_types=1);

namespace App\Filament\Gr\Resources\PrenotazioneResource\Pages;

use App\Enums\CategoriaPatente;
use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use App\Enums\TipoMezzo;
use App\Filament\Gr\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Services\PdfGenerator;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\View as ViewInfolist;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ViewPrenotazione extends ViewRecord
{
    protected static string $resource = PrenotazioneResource::class;

    private function prenotazione(): Prenotazione
    {
        $record = $this->getRecord();
        if (! $record instanceof Prenotazione) {
            throw new \UnexpectedValueException('Expected Prenotazione model.');
        }

        return $record;
    }

    public function getTitle(): string
    {
        return $this->prenotazione()->nome_evento;
    }

    /** Header: etichetta richiedente (BUG-05) + badge stato + data di invio, secondo il mockup "GR Dettaglio prenotazione". */
    public function getSubheading(): string|Htmlable|null
    {
        $pren = $this->prenotazione();

        return new HtmlString(view('filament.gr.resources.prenotazione-resource.pages.dettaglio-subheading', [
            'richiedenteHtml' => PrenotazioneResource::richiedenteLabel($pren),
            'status' => $pren->status,
            'dataRichiesta' => $this->dataRichiesta(),
        ])->render());
    }

    private function dataRichiesta(): ?Carbon
    {
        $pren = $this->prenotazione();

        $invio = $pren->history()
            ->where('status_to', PrenotazioneStatus::Inviata)
            ->oldest('created_at')
            ->first();

        $value = $invio !== null ? $invio->created_at : $pren->created_at;

        return $value !== null ? Carbon::parse($value) : null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Righe per la timeline "Storico" (US-022), più recenti in alto: icona/colore/titolo
     * derivati da `status_to` con una mappatura dedicata (vedi {@see self::historyIcon()},
     * {@see self::historyColor()}) invece di riusare `PrenotazioneStatus::color()` (che è
     * pensato per il badge di stato corrente, dove "Inviata assicurazione" è verde brand
     * come "Approvata" — qui invece la timeline deve distinguerle visivamente, come nel
     * mockup "GR Dettaglio Storico").
     *
     * @return list<array{icon: string, color: string, title: string, author: string, date: string, note: ?string}>
     */
    private function historyTimelineItems(): array
    {
        return $this->prenotazione()->history()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (PrenotazioneHistory $entry): array => [
                'icon' => self::historyIcon($entry->status_to),
                'color' => self::historyColor($entry->status_to),
                'title' => $entry->status_to->label(),
                'author' => self::historyAuthor($entry),
                'date' => $entry->created_at !== null ? Carbon::parse($entry->created_at)->translatedFormat('d F Y, H:i') : '—',
                'note' => $entry->note,
            ])
            ->all();
    }

    /** Estratto in un helper con parametro esplicitamente nullable per il falso positivo Larastan `nullsafe.neverNull` su relazioni `BelongsTo` nullable (vedi Codebase Patterns in progress.txt). */
    private static function historyAuthor(PrenotazioneHistory $entry): string
    {
        $user = $entry->user;

        return $user === null ? '—' : $user->name;
    }

    private static function historyIcon(PrenotazioneStatus $status): string
    {
        return match ($status) {
            PrenotazioneStatus::Bozza => 'heroicon-o-document-plus',
            PrenotazioneStatus::Inviata => 'heroicon-o-envelope',
            PrenotazioneStatus::Approvata => 'heroicon-o-check-circle',
            PrenotazioneStatus::Annullata => 'heroicon-o-x-circle',
            PrenotazioneStatus::InviatoPdfFirmato => 'heroicon-o-document-check',
            PrenotazioneStatus::InviatoAssicurazione => 'heroicon-o-paper-airplane',
            PrenotazioneStatus::Concluso => 'heroicon-o-check-badge',
        };
    }

    /** Prefisso della scala colore CSS (`tokens/colors.css`) da usare per icona/sfondo del pallino. */
    private static function historyColor(PrenotazioneStatus $status): string
    {
        return match ($status) {
            PrenotazioneStatus::Bozza, PrenotazioneStatus::Concluso => 'stone',
            PrenotazioneStatus::Inviata => 'warning',
            PrenotazioneStatus::Approvata => 'success',
            PrenotazioneStatus::Annullata => 'danger',
            PrenotazioneStatus::InviatoPdfFirmato, PrenotazioneStatus::InviatoAssicurazione => 'info',
        };
    }

    /**
     * Azione "Approva" (Decisione): estratta in un metodo per essere riusata identica sia
     * nella card desktop "Decisione" sia nella barra azioni fissa mobile (US-021) — stessa
     * form/logica, solo un `$name` diverso per evitare collisioni fra le due istanze
     * renderizzate contemporaneamente nel DOM (schema desktop + schema mobile).
     */
    private function approvaAction(Prenotazione $pren, callable $puoDecidere, string $name = 'approva'): InfolistAction
    {
        return InfolistAction::make($name)
            ->label('Approva richiesta')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (): bool => $puoDecidere() && auth()->user()->can('approve', $pren))
            ->form([
                Forms\Components\Select::make('torre_id_override')
                    ->label('Torre da assegnare')
                    ->helperText('Lascia vuoto per mantenere la torre scelta dalla sezione.')
                    ->options(Torre::where('is_active', true)->pluck('nome', 'id'))
                    ->nullable()
                    ->searchable(),
            ])
            ->action(function (array $data) use ($pren): void {
                $torreId = filled($data['torre_id_override'] ?? null)
                    ? (int) $data['torre_id_override']
                    : null;
                app(PrenotazioneStateMachine::class)->approva($pren, auth()->user(), $torreId);
                Notification::make()->title('Prenotazione approvata')->success()->send();
                $this->redirect(PrenotazioneResource::getUrl('index'));
            });
    }

    /** Azione "Rifiuta" (Decisione): vedi nota su {@see self::approvaAction()}. */
    private function rifiutaAction(Prenotazione $pren, callable $puoDecidere, string $name = 'rifiuta'): InfolistAction
    {
        return InfolistAction::make($name)
            ->label('Rifiuta — motivo obbligatorio')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->outlined()
            ->visible(fn (): bool => $puoDecidere() && auth()->user()->can('reject', $pren))
            ->form([
                Forms\Components\Textarea::make('motivo')
                    ->label('Motivo del rifiuto')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4),
            ])
            ->action(function (array $data) use ($pren): void {
                app(PrenotazioneStateMachine::class)->rifiuta($pren, auth()->user(), $data['motivo']);
                Notification::make()->title('Prenotazione rifiutata')->warning()->send();
                $this->redirect(PrenotazioneResource::getUrl('index'));
            });
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $pren = $this->prenotazione();

        $puoDecidere = fn (): bool => $pren->status === PrenotazioneStatus::Inviata;
        $puoRiassegnare = fn (): bool => in_array(
            $pren->status,
            [PrenotazioneStatus::Approvata, PrenotazioneStatus::InviatoPdfFirmato],
            strict: true,
        );
        $puoModificareDate = fn (): bool => in_array(
            $pren->status,
            [PrenotazioneStatus::Inviata, PrenotazioneStatus::Approvata, PrenotazioneStatus::InviatoPdfFirmato],
            strict: true,
        );
        $puoScaricarePdf = fn (): bool => in_array(
            $pren->status,
            [
                PrenotazioneStatus::Approvata,
                PrenotazioneStatus::InviatoPdfFirmato,
                PrenotazioneStatus::InviatoAssicurazione,
                PrenotazioneStatus::Concluso,
            ],
            strict: true,
        );
        $puoInviareAssicurazione = fn (): bool => $pren->status === PrenotazioneStatus::InviatoPdfFirmato;
        $puoConcludere = fn (): bool => $pren->status === PrenotazioneStatus::InviatoAssicurazione;

        return $infolist->schema([
            Tabs::make('tabs')
                ->tabs([
                    Tabs\Tab::make('Dettagli')
                        ->schema([
                            Grid::make(['default' => 1, 'lg' => 3])
                                ->schema([
                                    Group::make([
                                        Section::make('Quando e dove')
                                            ->icon('heroicon-o-calendar-days')
                                            ->iconColor('primary')
                                            ->schema([
                                                TextEntry::make('data_inizio_prenotazione')->label('Da')->date('d/m/Y'),
                                                TextEntry::make('data_fine_prenotazione')->label('A')->date('d/m/Y'),
                                                TextEntry::make('torre.nome')
                                                    ->label('Torre richiesta')
                                                    ->badge()
                                                    ->color(fn (Prenotazione $record): array => Color::hex(Torre::coloreHexPer($record->torre)))
                                                    ->default('Nessuna preferenza'),
                                                TextEntry::make('torre.indirizzo_deposito')->label('Deposito torre')->default('—'),
                                                TextEntry::make('motivo_rifiuto')
                                                    ->label('Motivo rifiuto')
                                                    ->visible(fn (Prenotazione $record): bool => filled($record->motivo_rifiuto))
                                                    ->columnSpanFull(),
                                            ])->columns(3),

                                        Section::make('Evento')
                                            ->icon('heroicon-o-flag')
                                            ->iconColor('primary')
                                            ->schema([
                                                TextEntry::make('nome_evento')->label('Nome evento'),
                                                TextEntry::make('tipo_evento')->label('Tipo'),
                                                TextEntry::make('indirizzo_evento')->label('Indirizzo'),
                                                TextEntry::make('data_inizio_evento')->label('Inizio evento')->date('d/m/Y'),
                                                TextEntry::make('data_fine_evento')->label('Fine evento')->date('d/m/Y'),
                                                TextEntry::make('descrizione_evento')->label('Descrizione')->columnSpanFull(),
                                            ])->columns(3),

                                        Section::make('Logistica e responsabile')
                                            ->icon('heroicon-o-truck')
                                            ->iconColor('primary')
                                            ->schema([
                                                TextEntry::make('tipo_mezzo')
                                                    ->label('Tipo mezzo')
                                                    ->formatStateUsing(fn (mixed $state): string => $state instanceof TipoMezzo ? $state->label() : (string) $state),
                                                TextEntry::make('categoria_patente_privato')
                                                    ->label('Categoria patente')
                                                    ->visible(fn (Prenotazione $record): bool => $record->tipo_mezzo === TipoMezzo::Privato)
                                                    ->formatStateUsing(fn (mixed $state): string => $state instanceof CategoriaPatente ? $state->label() : (string) $state),
                                                TextEntry::make('azienda_trasporto')->label('Azienda trasporto'),
                                                TextEntry::make('targa_autoveicolo')->label('Targa')->default('—'),
                                                TextEntry::make('data_ritiro')->label('Data ritiro')->date('d/m/Y')->placeholder('—'),
                                                TextEntry::make('luogo_ritiro')->label('Luogo ritiro')->default('—'),
                                                TextEntry::make('data_riconsegna')->label('Data riconsegna')->date('d/m/Y')->placeholder('—'),
                                                TextEntry::make('luogo_riconsegna')->label('Luogo riconsegna')->default('—'),
                                                TextEntry::make('responsabile_nome')->label('Responsabile')->columnSpanFull(),
                                                TextEntry::make('responsabile_tipo')
                                                    ->label('Qualifica')
                                                    ->formatStateUsing(fn (mixed $state): string => $state instanceof ResponsabileTipo ? $state->label() : (string) $state),
                                                TextEntry::make('responsabile_titolo_cai')->label('Titolo CAI')->default('—'),
                                                TextEntry::make('responsabile_telefono')->label('Telefono'),
                                                TextEntry::make('responsabile_email')->label('Email'),
                                            ])->columns(3),
                                    ])->columnSpan(['lg' => 2]),

                                    Group::make([
                                        Section::make('Decisione')
                                            ->icon('heroicon-o-scale')
                                            ->iconColor('success')
                                            ->visible($puoDecidere)
                                            ->schema([
                                                InfolistActions::make([
                                                    $this->approvaAction($pren, $puoDecidere),
                                                ])->fullWidth(),

                                                InfolistActions::make([
                                                    $this->rifiutaAction($pren, $puoDecidere),
                                                ])->fullWidth(),
                                            ]),

                                        Section::make('Documenti')
                                            ->icon('heroicon-o-document-arrow-down')
                                            ->iconColor('primary')
                                            ->visible($puoScaricarePdf)
                                            ->schema([
                                                InfolistActions::make([
                                                    InfolistAction::make('download_richiesta')
                                                        ->label('Scarica Richiesta parete')
                                                        ->icon('heroicon-o-document-arrow-down')
                                                        ->color('gray')
                                                        ->visible(fn (): bool => $puoScaricarePdf() && auth()->user()->can('generatePdfRichiesta', $pren))
                                                        ->action(fn () => response()->streamDownload(
                                                            fn () => print (app(PdfGenerator::class)->richiestaParete($pren)->output()),
                                                            "Richiesta_parete_{$pren->id}.pdf",
                                                            ['Content-Type' => 'application/pdf'],
                                                        )),
                                                ])->fullWidth(),

                                                InfolistActions::make([
                                                    InfolistAction::make('download_modulo3')
                                                        ->label('Scarica Modulo 3')
                                                        ->icon('heroicon-o-document-text')
                                                        ->color('gray')
                                                        ->visible(fn (): bool => $puoScaricarePdf() && auth()->user()->can('generatePdfModulo3', $pren))
                                                        ->action(fn () => response()->streamDownload(
                                                            fn () => print (app(PdfGenerator::class)->modulo3($pren, app(GrSettings::class))->output()),
                                                            "Modulo3_{$pren->id}.pdf",
                                                            ['Content-Type' => 'application/pdf'],
                                                        )),
                                                ])->fullWidth(),
                                            ]),

                                        Section::make('Altre azioni')
                                            ->icon('heroicon-o-ellipsis-horizontal-circle')
                                            ->iconColor('primary')
                                            ->visible(fn (): bool => $puoRiassegnare() || $puoModificareDate() || $puoInviareAssicurazione() || $puoConcludere())
                                            ->schema([
                                                InfolistActions::make([
                                                    InfolistAction::make('reassign_torre')
                                                        ->label('Riassegna torre')
                                                        ->icon('heroicon-o-arrow-path')
                                                        ->color('warning')
                                                        ->visible(fn (): bool => $puoRiassegnare() && auth()->user()->can('reassignTorre', $pren))
                                                        ->form([
                                                            Forms\Components\Select::make('torre_id')
                                                                ->label('Nuova torre')
                                                                ->options(Torre::where('is_active', true)->pluck('nome', 'id'))
                                                                ->required()
                                                                ->searchable(),
                                                        ])
                                                        ->action(function (array $data) use ($pren): void {
                                                            app(PrenotazioneStateMachine::class)->reassignTorre($pren, auth()->user(), (int) $data['torre_id']);
                                                            Notification::make()->title('Torre riassegnata')->success()->send();
                                                            $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $pren]));
                                                        }),
                                                ])->fullWidth(),

                                                InfolistActions::make([
                                                    InfolistAction::make('change_dates')
                                                        ->label('Modifica date trasporto')
                                                        ->icon('heroicon-o-calendar')
                                                        ->color('info')
                                                        ->visible(fn (): bool => $puoModificareDate() && auth()->user()->can('changeDates', $pren))
                                                        ->form([
                                                            Forms\Components\DatePicker::make('data_ritiro')
                                                                ->label('Data ritiro')
                                                                ->default($pren->data_ritiro?->toDateString())
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y'),

                                                            Forms\Components\DatePicker::make('data_riconsegna')
                                                                ->label('Data riconsegna')
                                                                ->default($pren->data_riconsegna?->toDateString())
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y')
                                                                ->afterOrEqual('data_ritiro'),

                                                            Forms\Components\Textarea::make('motivo')
                                                                ->label('Motivo della modifica')
                                                                ->required()
                                                                ->maxLength(1000)
                                                                ->rows(3),
                                                        ])
                                                        ->action(function (array $data) use ($pren): void {
                                                            app(PrenotazioneStateMachine::class)->changeDates(
                                                                $pren,
                                                                auth()->user(),
                                                                filled($data['data_ritiro']) ? (string) $data['data_ritiro'] : null,
                                                                filled($data['data_riconsegna']) ? (string) $data['data_riconsegna'] : null,
                                                                $data['motivo'],
                                                            );
                                                            Notification::make()->title('Date trasporto aggiornate')->success()->send();
                                                            $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $pren]));
                                                        }),
                                                ])->fullWidth(),

                                                InfolistActions::make([
                                                    InfolistAction::make('invia_assicurazione')
                                                        ->label('Invia all\'assicurazione')
                                                        ->icon('heroicon-o-envelope')
                                                        ->color('warning')
                                                        ->visible(fn (): bool => $puoInviareAssicurazione() && auth()->user()->can('sendInsurance', $pren))
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Invia Modulo 3 all\'assicurazione')
                                                        ->modalDescription(function (): string {
                                                            $emails = app(GrSettings::class)->emails_assicurazione;
                                                            $lista = implode(', ', $emails ?: ['(nessun destinatario configurato)']);

                                                            return "Verrà inviata un'email con il Modulo 3 allegato a: {$lista}. Questa azione cambia lo stato della prenotazione a INVIATO_ASSICURAZIONE.";
                                                        })
                                                        ->action(function () use ($pren): void {
                                                            app(PrenotazioneStateMachine::class)->inviaAssicurazione($pren, auth()->user());
                                                            Notification::make()->title('Modulo 3 inviato all\'assicurazione')->success()->send();
                                                            $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $pren]));
                                                        }),
                                                ])->fullWidth(),

                                                InfolistActions::make([
                                                    InfolistAction::make('concludi')
                                                        ->label('Segna come conclusa')
                                                        ->icon('heroicon-o-check-badge')
                                                        ->color('gray')
                                                        ->visible(fn (): bool => $puoConcludere() && auth()->user()->can('markConcluso', $pren))
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Concludi prenotazione')
                                                        ->modalDescription('Marca la prenotazione come CONCLUSA. Questa operazione è normalmente eseguita automaticamente dal sistema il giorno dopo la fine dell\'evento.')
                                                        ->action(function () use ($pren): void {
                                                            app(PrenotazioneStateMachine::class)->concludi($pren, auth()->user());
                                                            Notification::make()->title('Prenotazione conclusa')->success()->send();
                                                            $this->redirect(PrenotazioneResource::getUrl('index'));
                                                        }),
                                                ])->fullWidth(),

                                                TextEntry::make('altre_azioni_hint')
                                                    ->hiddenLabel()
                                                    ->getStateUsing('Disponibili dopo l\'approvazione e il caricamento del PDF firmato da parte della Sezione.')
                                                    ->color('gray')
                                                    ->size('sm'),
                                            ]),
                                    ])->columnSpan(['lg' => 1]),
                                ]),
                        ]),

                    Tabs\Tab::make('Allegati')
                        ->badge(fn (Prenotazione $record): int => collect([
                            'delibera_consiglio',
                            'autorizzazione_suolo_pubblico',
                            'autorizzazione_ztl',
                            'patente_responsabile',
                        ])->filter(fn (string $collection): bool => $record->getFirstMedia($collection) !== null)->count())
                        ->schema([
                            Section::make('Documenti caricati')
                                ->schema([
                                    TextEntry::make('delibera_file')
                                        ->label('Delibera del Consiglio')
                                        ->getStateUsing(function (Prenotazione $record): string {
                                            $m = $record->getFirstMedia('delibera_consiglio');

                                            return $m !== null ? $m->file_name : '—';
                                        })
                                        ->url(function (Prenotazione $record): ?string {
                                            $m = $record->getFirstMedia('delibera_consiglio');

                                            return $m !== null ? $m->getUrl() : null;
                                        })
                                        ->openUrlInNewTab(),

                                    TextEntry::make('suolo_file')
                                        ->label('Autorizzazione suolo pubblico')
                                        ->getStateUsing(function (Prenotazione $record): string {
                                            $m = $record->getFirstMedia('autorizzazione_suolo_pubblico');

                                            return $m !== null ? $m->file_name : '—';
                                        })
                                        ->url(function (Prenotazione $record): ?string {
                                            $m = $record->getFirstMedia('autorizzazione_suolo_pubblico');

                                            return $m !== null ? $m->getUrl() : null;
                                        })
                                        ->openUrlInNewTab(),

                                    TextEntry::make('ztl_file')
                                        ->label('Autorizzazione ZTL')
                                        ->getStateUsing(function (Prenotazione $record): string {
                                            $m = $record->getFirstMedia('autorizzazione_ztl');

                                            return $m !== null ? $m->file_name : '—';
                                        })
                                        ->url(function (Prenotazione $record): ?string {
                                            $m = $record->getFirstMedia('autorizzazione_ztl');

                                            return $m !== null ? $m->getUrl() : null;
                                        })
                                        ->openUrlInNewTab(),

                                    TextEntry::make('patente_file')
                                        ->label('Patente responsabile')
                                        ->getStateUsing(function (Prenotazione $record): string {
                                            $m = $record->getFirstMedia('patente_responsabile');

                                            return $m !== null ? $m->file_name : '—';
                                        })
                                        ->url(function (Prenotazione $record): ?string {
                                            $m = $record->getFirstMedia('patente_responsabile');

                                            return $m !== null ? $m->getUrl() : null;
                                        })
                                        ->openUrlInNewTab(),
                                ])->columns(2),
                        ]),

                    Tabs\Tab::make('Storico')
                        ->schema([
                            ViewInfolist::make('filament.gr.resources.prenotazione-resource.pages.storico-timeline')
                                ->viewData(['items' => $this->historyTimelineItems()]),
                        ]),
                ])
                ->columnSpanFull(),

            // Barra azioni fissa mobile (US-021, mockup "Mobile GR Dettaglio"): fuori dai
            // Tabs (rimane visibile su qualunque tab attiva), stesse azioni "Decisione" sopra
            // (self::approvaAction()/rifiutaAction()), solo un $name diverso per non collidere
            // con le istanze già montate nella card desktop. Stesse classi "fixed inset-x-0
            // bottom-0 md:hidden" già consolidate per il footer del Wizard (US-007): essendo
            // più alta della bottom-nav (US-002, stesso z-index) la sovrasta/nasconde
            // visivamente quando è visibile, coerente col mockup che non mostra la tab bar
            // in questa schermata.
            Group::make([
                InfolistActions::make([
                    $this->approvaAction($pren, $puoDecidere, 'approva_mobile'),
                ])->fullWidth(),

                InfolistActions::make([
                    $this->rifiutaAction($pren, $puoDecidere, 'rifiuta_mobile'),
                ])->fullWidth(),
            ])
                ->visible($puoDecidere)
                ->extraAttributes([
                    'class' => 'fixed inset-x-0 bottom-0 z-40 flex flex-col gap-2 border-t px-4 pb-[max(0.875rem,env(safe-area-inset-bottom))] pt-3.5 md:hidden',
                    'style' => 'background:var(--surface-page);border-color:var(--border-subtle)',
                ]),
        ]);
    }
}
