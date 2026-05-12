<?php

declare(strict_types=1);

namespace App\Filament\Gr\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use App\Filament\Gr\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Services\PdfGenerator;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approva')
                ->label('Approva')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->prenotazione()->status === PrenotazioneStatus::Inviata
                    && auth()->user()->can('approve', $this->prenotazione()))
                ->form([
                    Forms\Components\Select::make('torre_id_override')
                        ->label('Torre da assegnare')
                        ->helperText('Lascia vuoto per mantenere la torre scelta dalla sezione.')
                        ->options(Torre::where('is_active', true)->pluck('nome', 'id'))
                        ->nullable()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    $pren = $this->prenotazione();
                    $torreId = filled($data['torre_id_override'] ?? null)
                        ? (int) $data['torre_id_override']
                        : null;
                    app(PrenotazioneStateMachine::class)->approva($pren, auth()->user(), $torreId);
                    Notification::make()->title('Prenotazione approvata')->success()->send();
                    $this->redirect(PrenotazioneResource::getUrl('index'));
                }),

            Actions\Action::make('rifiuta')
                ->label('Rifiuta')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->prenotazione()->status === PrenotazioneStatus::Inviata
                    && auth()->user()->can('reject', $this->prenotazione()))
                ->form([
                    Forms\Components\Textarea::make('motivo')
                        ->label('Motivo del rifiuto')
                        ->required()
                        ->maxLength(1000)
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    app(PrenotazioneStateMachine::class)->rifiuta(
                        $this->prenotazione(),
                        auth()->user(),
                        $data['motivo'],
                    );
                    Notification::make()->title('Prenotazione rifiutata')->warning()->send();
                    $this->redirect(PrenotazioneResource::getUrl('index'));
                }),

            Actions\Action::make('reassign_torre')
                ->label('Riassegna torre')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => in_array(
                    $this->prenotazione()->status,
                    [PrenotazioneStatus::Approvata, PrenotazioneStatus::InviatoPdfFirmato],
                    strict: true,
                ) && auth()->user()->can('reassignTorre', $this->prenotazione()))
                ->form([
                    Forms\Components\Select::make('torre_id')
                        ->label('Nuova torre')
                        ->options(Torre::where('is_active', true)->pluck('nome', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    app(PrenotazioneStateMachine::class)->reassignTorre(
                        $this->prenotazione(),
                        auth()->user(),
                        (int) $data['torre_id'],
                    );
                    Notification::make()->title('Torre riassegnata')->success()->send();
                    $this->refreshFormData(['torre_id']);
                    $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $this->prenotazione()]));
                }),

            Actions\Action::make('change_dates')
                ->label('Modifica date trasporto')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->visible(fn (): bool => in_array(
                    $this->prenotazione()->status,
                    [PrenotazioneStatus::Inviata, PrenotazioneStatus::Approvata, PrenotazioneStatus::InviatoPdfFirmato],
                    strict: true,
                ) && auth()->user()->can('changeDates', $this->prenotazione()))
                ->form(function (): array {
                    $p = $this->prenotazione();

                    return [
                        Forms\Components\DatePicker::make('data_ritiro')
                            ->label('Data ritiro')
                            ->default($p->data_ritiro?->toDateString())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('data_riconsegna')
                            ->label('Data riconsegna')
                            ->default($p->data_riconsegna?->toDateString())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('data_ritiro'),

                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo della modifica')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),
                    ];
                })
                ->action(function (array $data): void {
                    app(PrenotazioneStateMachine::class)->changeDates(
                        $this->prenotazione(),
                        auth()->user(),
                        filled($data['data_ritiro']) ? (string) $data['data_ritiro'] : null,
                        filled($data['data_riconsegna']) ? (string) $data['data_riconsegna'] : null,
                        $data['motivo'],
                    );
                    Notification::make()->title('Date trasporto aggiornate')->success()->send();
                    $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $this->prenotazione()]));
                }),

            Actions\Action::make('download_richiesta')
                ->label('Scarica Richiesta parete')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => in_array(
                    $this->prenotazione()->status,
                    [
                        PrenotazioneStatus::Approvata,
                        PrenotazioneStatus::InviatoPdfFirmato,
                        PrenotazioneStatus::InviatoAssicurazione,
                        PrenotazioneStatus::Concluso,
                    ],
                    strict: true,
                ) && auth()->user()->can('generatePdfRichiesta', $this->prenotazione()))
                ->action(function () {
                    $p = $this->prenotazione();

                    return response()->streamDownload(
                        fn () => print (app(PdfGenerator::class)->richiestaParete($p)->output()),
                        "Richiesta_parete_{$p->id}.pdf",
                        ['Content-Type' => 'application/pdf'],
                    );
                }),

            Actions\Action::make('download_modulo3')
                ->label('Scarica Modulo 3')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn (): bool => in_array(
                    $this->prenotazione()->status,
                    [
                        PrenotazioneStatus::Approvata,
                        PrenotazioneStatus::InviatoPdfFirmato,
                        PrenotazioneStatus::InviatoAssicurazione,
                        PrenotazioneStatus::Concluso,
                    ],
                    strict: true,
                ) && auth()->user()->can('generatePdfModulo3', $this->prenotazione()))
                ->action(function () {
                    $p = $this->prenotazione();
                    $settings = app(GrSettings::class);

                    return response()->streamDownload(
                        fn () => print (app(PdfGenerator::class)->modulo3($p, $settings)->output()),
                        "Modulo3_{$p->id}.pdf",
                        ['Content-Type' => 'application/pdf'],
                    );
                }),

            Actions\Action::make('invia_assicurazione')
                ->label('Invia all\'assicurazione')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->visible(fn (): bool => $this->prenotazione()->status === PrenotazioneStatus::InviatoPdfFirmato
                    && auth()->user()->can('sendInsurance', $this->prenotazione()))
                ->requiresConfirmation()
                ->modalHeading('Invia Modulo 3 all\'assicurazione')
                ->modalDescription(function (): string {
                    $emails = app(GrSettings::class)->emails_assicurazione;
                    $lista = implode(', ', $emails ?: ['(nessun destinatario configurato)']);

                    return "Verrà inviata un'email con il Modulo 3 allegato a: {$lista}. Questa azione cambia lo stato della prenotazione a INVIATO_ASSICURAZIONE.";
                })
                ->action(function (): void {
                    app(PrenotazioneStateMachine::class)->inviaAssicurazione(
                        $this->prenotazione(),
                        auth()->user(),
                    );
                    Notification::make()->title('Modulo 3 inviato all\'assicurazione')->success()->send();
                    $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $this->prenotazione()]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('tabs')
                ->tabs([
                    Tabs\Tab::make('Dettagli')
                        ->schema([
                            Section::make('Prenotazione torre')
                                ->schema([
                                    TextEntry::make('proprietario_label')
                                        ->label('Sezione / Sottosezione')
                                        ->getStateUsing(fn (Prenotazione $record): string => $record->proprietario_label),
                                    TextEntry::make('status')
                                        ->label('Stato')
                                        ->badge()
                                        ->formatStateUsing(fn (PrenotazioneStatus $state): string => $state->label())
                                        ->color(fn (PrenotazioneStatus $state): string => $state->color()),
                                    TextEntry::make('torre.nome')->label('Torre')->badge()->default('—'),
                                    TextEntry::make('torre.indirizzo_deposito')->label('Indirizzo deposito torre')->default('—'),
                                    TextEntry::make('data_inizio_prenotazione')->label('Da')->date('d/m/Y'),
                                    TextEntry::make('data_fine_prenotazione')->label('A')->date('d/m/Y'),
                                    TextEntry::make('motivo_rifiuto')->label('Motivo rifiuto')->default('—')->columnSpanFull(),
                                ])->columns(3),

                            Section::make('Evento')
                                ->schema([
                                    TextEntry::make('nome_evento')->label('Nome evento'),
                                    TextEntry::make('tipo_evento')->label('Tipo'),
                                    TextEntry::make('indirizzo_evento')->label('Indirizzo'),
                                    TextEntry::make('data_inizio_evento')->label('Inizio evento')->date('d/m/Y'),
                                    TextEntry::make('data_fine_evento')->label('Fine evento')->date('d/m/Y'),
                                    TextEntry::make('descrizione_evento')->label('Descrizione')->columnSpanFull(),
                                ])->columns(3),

                            Section::make('Logistica trasporto')
                                ->schema([
                                    TextEntry::make('azienda_trasporto')->label('Azienda trasporto'),
                                    TextEntry::make('targa_autoveicolo')->label('Targa')->default('—'),
                                    TextEntry::make('data_ritiro')->label('Data ritiro')->date('d/m/Y')->placeholder('—'),
                                    TextEntry::make('luogo_ritiro')->label('Luogo ritiro')->default('—'),
                                    TextEntry::make('data_riconsegna')->label('Data riconsegna')->date('d/m/Y')->placeholder('—'),
                                    TextEntry::make('luogo_riconsegna')->label('Luogo riconsegna')->default('—'),
                                ])->columns(3),

                            Section::make('Responsabile in loco')
                                ->schema([
                                    TextEntry::make('responsabile_nome')->label('Nome'),
                                    TextEntry::make('responsabile_tipo')
                                        ->label('Qualifica')
                                        ->formatStateUsing(fn (mixed $state): string => $state instanceof ResponsabileTipo ? $state->label() : (string) $state),
                                    TextEntry::make('responsabile_titolo_cai')->label('Titolo CAI')->default('—'),
                                    TextEntry::make('responsabile_codice_cai')->label('Codice CAI')->default('—'),
                                    TextEntry::make('responsabile_telefono')->label('Telefono'),
                                    TextEntry::make('responsabile_email')->label('Email'),
                                ])->columns(3),
                        ]),

                    Tabs\Tab::make('Allegati')
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
                            RepeatableEntry::make('history')
                                ->label('')
                                ->getStateUsing(fn (Prenotazione $record) => $record->history()->orderBy('created_at', 'desc')->get())
                                ->schema([
                                    TextEntry::make('status_from')
                                        ->label('Da')
                                        ->formatStateUsing(fn (mixed $state): string => $state instanceof PrenotazioneStatus ? $state->label() : '—'),
                                    TextEntry::make('status_to')
                                        ->label('A')
                                        ->formatStateUsing(fn (PrenotazioneStatus $state): string => $state->label()),
                                    TextEntry::make('user.name')->label('Operatore')->default('—'),
                                    TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                                    TextEntry::make('note')->label('Note')->default('—')->columnSpanFull(),
                                ])->columns(4),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
