<?php

declare(strict_types=1);

namespace App\Filament\Gr\Widgets;

use App\Enums\PrenotazioneStatus;
use App\Filament\Gr\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class PrenotazioniDaApprovareWidget extends Widget
{
    protected static string $view = 'filament.gr.widgets.prenotazioni-da-approvare';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /** @return Collection<int, Prenotazione> */
    public function getPrenotazioniDaApprovare(): Collection
    {
        return Prenotazione::with(['sezione', 'sottosezione', 'torre'])
            ->where('status', PrenotazioneStatus::Inviata->value)
            ->orderBy('data_inizio_prenotazione')
            ->limit(5)
            ->get();
    }

    public function getCountDaApprovare(): int
    {
        return Prenotazione::where('status', PrenotazioneStatus::Inviata->value)->count();
    }

    public function getCountApprovateProssimi30Giorni(): int
    {
        return Prenotazione::where('status', PrenotazioneStatus::Approvata->value)
            ->where('data_inizio_prenotazione', '<=', now()->addDays(30))
            ->where('data_fine_prenotazione', '>=', now())
            ->count();
    }

    public function getUrlListaDaApprovare(): string
    {
        return PrenotazioneResource::getUrl('index').'?activeTab=da_approvare';
    }

    public function getUrlPrenotazione(int $id): string
    {
        return PrenotazioneResource::getUrl('view', ['record' => $id]);
    }
}
