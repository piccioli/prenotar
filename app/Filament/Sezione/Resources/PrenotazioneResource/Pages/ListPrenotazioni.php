<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPrenotazioni extends ListRecords
{
    protected static string $resource = PrenotazioneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuova prenotazione')
                ->visible(fn (): bool => auth()->user()->can('create', Prenotazione::class)),
        ];
    }

    public function getTabs(): array
    {
        $userId = auth()->id();
        $statiFinal = [PrenotazioneStatus::Concluso->value, PrenotazioneStatus::Annullata->value];

        return [
            'attive' => Tab::make('Attive')
                ->badge(
                    Prenotazione::query()->where('user_id', $userId)->whereNotIn('status', $statiFinal)->count()
                )
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotIn('status', $statiFinal)),

            'archivio' => Tab::make('Archivio')
                ->badge(
                    Prenotazione::query()->where('user_id', $userId)->whereIn('status', $statiFinal)->count()
                )
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', $statiFinal)),
        ];
    }
}
