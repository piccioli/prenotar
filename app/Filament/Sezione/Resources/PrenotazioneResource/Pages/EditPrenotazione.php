<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Services\PrenotazioneStateMachine;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPrenotazione extends EditRecord
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
            Actions\Action::make('invia_richiesta')
                ->label('Invia richiesta al GR')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => $this->prenotazione()->status === PrenotazioneStatus::Bozza
                    && $this->prenotazione()->hasMedia('delibera_consiglio'))
                ->requiresConfirmation()
                ->modalHeading('Invia richiesta al GR')
                ->modalDescription('Confermi l\'invio della richiesta? Dopo l\'invio non potrai più modificare la prenotazione.')
                ->action(function (): void {
                    $prenotazione = $this->prenotazione();
                    app(PrenotazioneStateMachine::class)->inviaRichiesta($prenotazione, auth()->user());
                    Notification::make()
                        ->title('Richiesta inviata')
                        ->body('La richiesta è stata inviata al GR Lombardia.')
                        ->success()
                        ->send();
                    $this->redirect(PrenotazioneResource::getUrl('view', ['record' => $prenotazione]));
                }),

            Actions\Action::make('elimina_bozza')
                ->label('Elimina bozza')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => $this->prenotazione()->status === PrenotazioneStatus::Bozza)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->prenotazione()->delete();
                    Notification::make()
                        ->title('Bozza eliminata')
                        ->success()
                        ->send();
                    $this->redirect(PrenotazioneResource::getUrl('index'));
                }),

            Actions\ViewAction::make(),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('update', $this->prenotazione()), 403);
    }
}
