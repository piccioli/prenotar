<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <label for="filtro-torre" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Filtra torre:
            </label>
            <select
                id="filtro-torre"
                wire:model.live="filtroTorreId"
                class="block rounded-lg border-gray-300 py-1.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                <option value="">Tutte le torri</option>
                @foreach($this->getTorri() as $torre)
                    <option value="{{ $torre->id }}">{{ $torre->nome }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm" style="background-color:#2563eb"></span>
                Torre 1
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3 w-3 rounded-sm" style="background-color:#ea580c"></span>
                Torre 2
            </span>
        </div>
    </div>

    @livewire(\App\Filament\Sezione\Widgets\CalendarioPrenotazioniWidget::class)
</x-filament-panels::page>
