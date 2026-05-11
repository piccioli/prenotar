<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Pages;

use App\Models\Torre;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class CalendarioPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.sezione.pages.calendario';

    protected static ?string $navigationLabel = 'Calendario torri';

    protected static ?string $title = 'Calendario torri';

    protected static ?string $slug = 'calendario';

    public ?int $filtroTorreId = null;

    public function updatedFiltroTorreId(): void
    {
        $this->dispatch('torre-filter-changed', torreId: $this->filtroTorreId);
    }

    /** @return Collection<int, Torre> */
    public function getTorri(): Collection
    {
        return Torre::query()->where('is_active', true)->orderBy('nome')->get();
    }
}
