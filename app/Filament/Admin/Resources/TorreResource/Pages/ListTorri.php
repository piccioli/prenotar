<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TorreResource\Pages;

use App\Filament\Admin\Resources\TorreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListTorri extends ListRecords
{
    protected static string $resource = TorreResource::class;

    /**
     * Restyle (US-029): il mockup "Admin Torri" presenta le torri come card a
     * griglia (non una tabella dati) con l'indirizzo di deposito in evidenza —
     * dataset ridotto e fisso (2 torri), niente `{{ $this->table }}` in questa
     * view. `getTableRecords()` resta la fonte dati (stessi filtri/ordinamento
     * configurati in `TorreResource::table()`), stesso pattern già usato per
     * le liste a card mobile (US-013/US-028).
     */
    protected static string $view = 'filament.admin.resources.torre-resource.pages.list-torri';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuova torre')];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Il colore assegnato qui identifica la torre in tutta l\'app: calendario, badge, dettagli.';
    }
}
