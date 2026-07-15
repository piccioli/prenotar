<?php

declare(strict_types=1);

namespace App\Filament\Gr\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Filament\Gr\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\Sottosezione;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListPrenotazioni extends ListRecords
{
    protected static string $resource = PrenotazioneResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return sprintf(
            'Tutte le richieste delle %d Sezioni e %d Sottosezioni, dalla più vicina.',
            Sezione::count(),
            Sottosezione::count(),
        );
    }

    public function getTabs(): array
    {
        $statiFinal = [PrenotazioneStatus::Concluso->value, PrenotazioneStatus::Annullata->value];

        return [
            'da_approvare' => Tab::make('Da approvare')
                ->badge(Prenotazione::where('status', PrenotazioneStatus::Inviata->value)->count())
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PrenotazioneStatus::Inviata->value)),

            'attive' => Tab::make('Attive')
                ->badge(Prenotazione::whereNotIn('status', $statiFinal)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotIn('status', $statiFinal)),

            'archivio' => Tab::make('Archivio')
                ->badge(Prenotazione::whereIn('status', $statiFinal)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', $statiFinal)),
        ];
    }
}
