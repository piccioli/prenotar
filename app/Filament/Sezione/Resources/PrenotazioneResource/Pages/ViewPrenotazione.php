<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Services\PrenotazioneStateMachine;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Http\UploadedFile;

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
            Actions\EditAction::make()
                ->visible(fn (): bool => auth()->user()->can('update', $this->prenotazione())),

            Actions\Action::make('invia_richiesta')
                ->label('Invia richiesta al GR')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => $this->prenotazione()->status === PrenotazioneStatus::Bozza
                    && $this->prenotazione()->hasMedia('delibera_consiglio')
                    && auth()->user()->can('update', $this->prenotazione()))
                ->requiresConfirmation()
                ->action(function (): void {
                    app(PrenotazioneStateMachine::class)->inviaRichiesta($this->prenotazione(), auth()->user());
                    Notification::make()
                        ->title('Richiesta inviata')
                        ->success()
                        ->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('upload_pdf_firmato')
                ->label('Carica PDF firmato')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()->can('loadSignedPdf', $this->prenotazione()))
                ->form([
                    Forms\Components\FileUpload::make('pdf')
                        ->label('PDF della richiesta firmato dal Presidente')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required()
                        ->disk('local')
                        ->visibility('private')
                        ->maxSize(10240),
                ])
                ->action(function (array $data): void {
                    $file = $data['pdf'];
                    if (is_string($file)) {
                        $file = new UploadedFile(
                            storage_path('app/livewire-tmp/'.$file),
                            $file,
                            'application/pdf',
                            null,
                            true,
                        );
                    }
                    app(PrenotazioneStateMachine::class)->caricaPdfFirmato(
                        $this->prenotazione(),
                        auth()->user(),
                        $file,
                    );
                    Notification::make()->title('PDF firmato caricato')->success()->send();
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
