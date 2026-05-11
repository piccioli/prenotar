<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    {{-- Card: Da approvare --}}
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Da approvare</p>
                <p class="mt-1 text-3xl font-semibold text-warning-600 dark:text-warning-400">
                    {{ $this->getCountDaApprovare() }}
                </p>
            </div>
            <x-heroicon-o-clock class="h-8 w-8 text-warning-500" />
        </div>
        <a href="{{ $this->getUrlListaDaApprovare() }}"
           class="mt-3 inline-flex items-center text-sm text-primary-600 hover:underline dark:text-primary-400">
            Vedi tutte &rarr;
        </a>
    </div>

    {{-- Card: Approvate prossimi 30 gg --}}
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Approvate (prossimi 30 gg)</p>
                <p class="mt-1 text-3xl font-semibold text-success-600 dark:text-success-400">
                    {{ $this->getCountApprovateProssimi30Giorni() }}
                </p>
            </div>
            <x-heroicon-o-check-circle class="h-8 w-8 text-success-500" />
        </div>
    </div>
</div>

@if($this->getCountDaApprovare() > 0)
<div class="mt-4 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="border-b border-gray-100 px-6 py-3 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Richieste in attesa di approvazione</h3>
    </div>
    <ul class="divide-y divide-gray-100 dark:divide-white/10">
        @foreach($this->getPrenotazioniDaApprovare() as $pren)
        <li class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                    {{ $pren->proprietario_label }}
                </p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ $pren->nome_evento }}
                    · {{ $pren->data_inizio_prenotazione->format('d/m/Y') }}–{{ $pren->data_fine_prenotazione->format('d/m/Y') }}
                    @if($pren->torre)
                    · {{ $pren->torre->nome }}
                    @endif
                </p>
            </div>
            <a href="{{ $this->getUrlPrenotazione($pren->id) }}"
               class="ml-4 shrink-0 text-xs text-primary-600 hover:underline dark:text-primary-400">
                Vedi &rarr;
            </a>
        </li>
        @endforeach
    </ul>
</div>
@endif
