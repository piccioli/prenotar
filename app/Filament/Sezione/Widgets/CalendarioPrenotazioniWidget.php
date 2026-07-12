<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Widgets;

use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\Torre;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarioPrenotazioniWidget extends FullCalendarWidget
{
    protected static string $view = 'filament.sezione.widgets.calendario-prenotazioni';

    protected int|string|array $columnSpan = 'full';

    public ?int $filtroTorreId = null;

    public ?string $previewInizio = null;

    public ?string $previewFine = null;

    /**
     * @param  array{start: string, end: string, timezone: string}  $info
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(array $info): array
    {
        $start = Carbon::parse($info['start']);
        $end = Carbon::parse($info['end']);

        $torriColori = $this->torriColori();

        $eventi = Prenotazione::eventiCalendarioPubblico($start, $end, $this->filtroTorreId)
            ->map(function (Prenotazione $pren) use ($torriColori): array {
                $torreId = $pren->torre_id;
                $colore = ($torreId !== null && isset($torriColori[$torreId]))
                    ? $torriColori[$torreId]
                    : Torre::COLORE_DEFAULT;

                $torreNome = $pren->torre !== null ? $pren->torre->nome : 'Senza torre';
                $diPropria = $pren->user_id === auth()->id();

                $evento = EventData::make()
                    ->id($pren->id)
                    ->title($diPropria ? $pren->nome_evento : $torreNome)
                    ->start($pren->data_inizio_prenotazione)
                    ->end($pren->data_fine_prenotazione->addDay())
                    ->backgroundColor($colore)
                    ->borderColor($colore)
                    ->allDay(true);

                if ($diPropria) {
                    $evento->url(PrenotazioneResource::getUrl('view', ['record' => $pren], panel: 'sezione'));
                }

                return $evento->toArray();
            })
            ->values()
            ->all();

        if ($this->previewInizio !== null && $this->previewFine !== null) {
            $eventi[] = EventData::make()
                ->id('preview')
                ->title('Anteprima periodo')
                ->start($this->previewInizio)
                ->end(Carbon::parse($this->previewFine)->addDay()->toDateString())
                ->backgroundColor('#facc15')
                ->borderColor('#f59e0b')
                ->textColor('#78350f')
                ->allDay(true)
                ->toArray();
        }

        return $eventi;
    }

    #[On('torre-filter-changed')]
    public function onTorreFilterChanged(?int $torreId): void
    {
        $this->filtroTorreId = $torreId;
        $this->refreshRecords();
    }

    #[On('preview-range-changed')]
    public function onPreviewRangeChanged(?string $inizio, ?string $fine): void
    {
        $this->previewInizio = $inizio;
        $this->previewFine = $fine;
        $this->refreshRecords();
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'locale' => 'it',
            'firstDay' => 1,
            'selectable' => false,
            'editable' => false,
            'eventDisplay' => 'block',
        ];
    }

    /**
     * Override applicato solo sotto il breakpoint mobile (768px), vedi vista Blade
     * `calendario-prenotazioni.blade.php`: FullCalendar mostra un'agenda/lista invece
     * della griglia mensile, illeggibile a schermi stretti.
     *
     * @return array<string, mixed>
     */
    public function mobileConfig(): array
    {
        return [
            'initialView' => 'listMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => '',
            ],
        ];
    }

    /** @return array<int, string> */
    private function torriColori(): array
    {
        return Torre::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (Torre $torre): array => [$torre->id => Torre::coloreHexPer($torre)])
            ->all();
    }
}
