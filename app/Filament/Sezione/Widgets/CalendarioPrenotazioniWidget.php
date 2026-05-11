<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Widgets;

use App\Models\Prenotazione;
use App\Models\Torre;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarioPrenotazioniWidget extends FullCalendarWidget
{
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
                    : '#6b7280';

                $torreNome = $pren->torre !== null ? $pren->torre->nome : 'Senza torre';

                return EventData::make()
                    ->id($pren->id)
                    ->title($torreNome)
                    ->start($pren->data_inizio_prenotazione)
                    ->end($pren->data_fine_prenotazione->addDay())
                    ->backgroundColor($colore)
                    ->borderColor($colore)
                    ->allDay(true)
                    ->toArray();
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

    /** @return array<int, string> */
    private function torriColori(): array
    {
        $palette = ['#2563eb', '#ea580c', '#16a34a', '#9333ea'];
        $map = [];
        $i = 0;
        foreach (Torre::query()->where('is_active', true)->orderBy('id')->get() as $torre) {
            $map[$torre->id] = $palette[$i] ?? '#6b7280';
            $i++;
        }

        return $map;
    }
}
