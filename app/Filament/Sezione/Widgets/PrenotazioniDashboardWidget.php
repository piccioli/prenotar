<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Widgets;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Pages\CalendarioPage;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Settings\GrSettings;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
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

    public function getUrlDettaglio(Prenotazione $prenotazione): string
    {
        return PrenotazioneResource::getUrl('view', ['record' => $prenotazione]);
    }

    public function getUrlCalendario(): string
    {
        return CalendarioPage::getUrl();
    }

    /**
     * Scadenza per il caricamento del PDF firmato (T-10, §5.1.5), o null se non
     * pertinente per lo stato corrente della prenotazione.
     */
    public function getScadenzaPdfFirmato(Prenotazione $prenotazione): ?Carbon
    {
        if ($prenotazione->status !== PrenotazioneStatus::Approvata || $prenotazione->pdf_firmato_at !== null) {
            return null;
        }

        $giorniMinimi = app(GrSettings::class)->giorni_minimi_caricamento_documenti;

        return $prenotazione->data_inizio_evento->copy()->subDays($giorniMinimi);
    }
}
