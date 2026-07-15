<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListPrenotazioni extends ListRecords
{
    protected static string $resource = PrenotazioneResource::class;

    /**
     * Override del template stock (US-013): sotto il breakpoint mobile, la
     * tabella lascia spazio a una lista di card (mockup 'Mobile Sezione Lista'),
     * riusando le stesse tab/ordinamento/paginazione via `$this->getTableRecords()`.
     */
    protected static string $view = 'filament.sezione.resources.prenotazione-resource.pages.list-prenotazioni';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuova prenotazione')
                ->visible(fn (): bool => auth()->user()->can('create', Prenotazione::class)),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Ordinate dalla data più vicina (BUG-01). Le concluse e annullate sono in Archivio (BUG-02).';
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
