<x-filament-panels::page>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <label for="filtro-torre-gr" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Filtra torre:
            </label>
            <select
                id="filtro-torre-gr"
                wire:model.live="filtroTorreId"
                class="block rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                <option value="">Tutte le torri</option>
                @foreach($this->getTorri() as $torre)
                    <option value="{{ $torre->id }}">{{ $torre->nome }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @foreach($this->getTorri() as $torre)
                <span
                    class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-bold"
                    style="background-color:color-mix(in srgb, {{ \App\Models\Torre::coloreHexPer($torre) }} 16%, white); color:{{ \App\Models\Torre::coloreHexPer($torre) }}"
                >
                    <span class="inline-block h-[11px] w-[11px] rounded" style="background-color:{{ \App\Models\Torre::coloreHexPer($torre) }}"></span>
                    {{ $torre->nome }} — dep. {{ $torre->indirizzo_deposito }}
                </span>
            @endforeach
        </div>
    </div>

    @livewire(\App\Filament\Sezione\Widgets\CalendarioPrenotazioniWidget::class)

    <div class="mt-4 flex items-center gap-2.5 text-[13px]" style="color:var(--stone-500)">
        <x-filament::icon icon="heroicon-o-cursor-arrow-rays" class="h-[15px] w-[15px] flex-shrink-0" />
        Click su un evento per aprire il dettaglio della prenotazione.
    </div>
</x-filament-panels::page>
