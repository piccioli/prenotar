<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use Filament\Actions;
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
            Actions\ViewAction::make(),
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('update', $this->prenotazione()), 403);
    }
}
