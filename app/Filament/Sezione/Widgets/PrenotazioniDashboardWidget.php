<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Widgets;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PrenotazioniDashboardWidget extends Widget
{
    protected static string $view = 'filament.sezione.widgets.prenotazioni-dashboard';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getPrenotazioneAttiva(): ?Prenotazione
    {
        return Prenotazione::query()
            ->where('user_id', Auth::id())
            ->whereIn('status', [
                PrenotazioneStatus::Inviata->value,
                PrenotazioneStatus::Approvata->value,
                PrenotazioneStatus::InviatoPdfFirmato->value,
                PrenotazioneStatus::InviatoAssicurazione->value,
            ])
            ->latest('data_inizio_prenotazione')
            ->first();
    }

    public function getUrlNuovaPrenotazione(): string
    {
        return PrenotazioneResource::getUrl('create');
    }

    public function getUrlListaPrenotazioni(): string
    {
        return PrenotazioneResource::getUrl('index');
    }
}
