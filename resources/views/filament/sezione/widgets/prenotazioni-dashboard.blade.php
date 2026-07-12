<x-filament-widgets::widget>
    @php $attiva = $this->getPrenotazioneAttiva() @endphp

    @if($attiva)
        @php $scadenzaPdf = $this->getScadenzaPdfFirmato($attiva) @endphp
        <div class="flex flex-col gap-4">
            <div
                class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                style="border-radius:20px"
            >
                <div class="flex flex-col gap-5 p-6 sm:p-8">
                    <div
                        class="flex flex-col items-start justify-between gap-6 border-b pb-5 sm:flex-row sm:items-center"
                        style="border-color:var(--border-subtle)"
                    >
                        <div class="flex flex-col gap-2.5">
                            <p class="text-xs font-extrabold uppercase tracking-widest" style="color:var(--text-brand)">
                                Prossima prenotazione
                            </p>
                            <p class="text-xl font-extrabold tracking-tight text-gray-950 dark:text-white">
                                {{ $attiva->nome_evento }}
                            </p>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <x-filament::badge :color="$attiva->status->color()">
                                    {{ $attiva->status->label() }}
                                </x-filament::badge>
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide"
                                    style="background:var(--stone-100);color:var(--stone-800)"
                                >
                                    <span
                                        class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                        style="background-color:{{ \App\Models\Torre::coloreHexPer($attiva->torre) }}"
                                    ></span>
                                    {{ $attiva->torre?->nome ?? 'Torre da assegnare' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex w-full flex-shrink-0 gap-2 md:w-auto">
                            <x-filament::button tag="a" :href="$this->getUrlDettaglio($attiva)" class="w-full md:w-auto">
                                Vedi dettaglio
                            </x-filament::button>
                            <x-filament::button tag="a" :href="$this->getUrlCalendario()" color="gray" outlined class="hidden md:inline-flex">
                                Apri calendario
                            </x-filament::button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 border-b pb-5 sm:grid-cols-3" style="border-color:var(--border-subtle)">
                        <div class="flex items-start gap-3">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="mt-0.5 h-5 w-5 flex-shrink-0" style="color:var(--text-brand)" />
                            <div class="flex flex-col gap-0.5">
                                <p class="text-xs font-bold uppercase tracking-wide" style="color:var(--text-muted)">Periodo evento</p>
                                <p class="text-sm font-bold text-gray-950 dark:text-white">
                                    {{ $attiva->data_inizio_evento->translatedFormat('d M Y') }} – {{ $attiva->data_fine_evento->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <x-filament::icon icon="heroicon-o-truck" class="mt-0.5 h-5 w-5 flex-shrink-0" style="color:var(--text-brand)" />
                            <div class="flex flex-col gap-0.5">
                                <p class="text-xs font-bold uppercase tracking-wide" style="color:var(--text-muted)">Ritiro / riconsegna</p>
                                <p class="text-sm font-bold text-gray-950 dark:text-white">
                                    @if($attiva->data_ritiro && $attiva->data_riconsegna)
                                        {{ $attiva->data_ritiro->translatedFormat('d M') }} · {{ $attiva->data_riconsegna->translatedFormat('d M Y') }}
                                    @else
                                        Da definire
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <x-filament::icon icon="heroicon-o-map-pin" class="mt-0.5 h-5 w-5 flex-shrink-0" style="color:var(--text-brand)" />
                            <div class="flex flex-col gap-0.5">
                                <p class="text-xs font-bold uppercase tracking-wide" style="color:var(--text-muted)">Deposito torre</p>
                                <p class="text-sm font-bold text-gray-950 dark:text-white">
                                    {{ $attiva->torre?->indirizzo_deposito ?? 'Torre non ancora assegnata' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($scadenzaPdf)
                        <div
                            class="flex flex-col items-start gap-3 rounded-xl border px-4 py-3.5 sm:flex-row sm:items-center"
                            style="background:var(--surface-accent);border-color:var(--larch-200)"
                        >
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 flex-shrink-0" style="color:var(--text-accent)" />
                            <p class="flex-1 text-sm" style="color:var(--stone-800)">
                                <span class="font-bold">Prossimo passo:</span>
                                carica il PDF firmato della delibera del consiglio entro il
                                <span class="font-bold">{{ $scadenzaPdf->translatedFormat('d MMMM Y') }}</span>
                                per completare la pratica.
                            </p>
                            <x-filament::button tag="a" :href="$this->getUrlDettaglio($attiva)" color="gray" outlined size="sm">
                                Carica PDF firmato
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 md:hidden">
                <a
                    href="{{ $this->getUrlNuovaPrenotazione() }}"
                    class="flex min-h-[44px] flex-col gap-2 rounded-2xl border bg-white p-4 dark:bg-gray-900"
                    style="border-color:var(--border-subtle)"
                >
                    <x-filament::icon icon="heroicon-o-plus" class="h-[22px] w-[22px]" style="color:var(--text-brand)" />
                    <span class="text-sm font-extrabold leading-tight text-gray-950 dark:text-white">Nuova prenotazione</span>
                </a>
                <a
                    href="{{ $this->getUrlCalendario() }}"
                    class="flex min-h-[44px] flex-col gap-2 rounded-2xl border bg-white p-4 dark:bg-gray-900"
                    style="border-color:var(--border-subtle)"
                >
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-[22px] w-[22px]" style="color:var(--text-brand)" />
                    <span class="text-sm font-extrabold leading-tight text-gray-950 dark:text-white">Calendario torri</span>
                </a>
            </div>
        </div>
    @else
        <div
            class="flex flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed bg-white p-16 text-center dark:bg-gray-900"
            style="border-color:var(--border-default)"
        >
            <div class="flex h-[72px] w-[72px] items-center justify-center rounded-full" style="background:var(--surface-subtle)">
                <x-filament::icon icon="heroicon-o-flag" class="h-9 w-9" style="color:var(--text-brand)" />
            </div>
            <p class="text-xl font-extrabold tracking-tight text-gray-950 dark:text-white">
                Nessuna prenotazione attiva
            </p>
            <p class="max-w-md text-sm leading-relaxed" style="color:var(--text-muted)">
                La tua sezione non ha richieste in corso. Puoi richiedere una delle due torri di arrampicata con la
                procedura guidata: bastano 5 passi.
            </p>
            <div class="mt-1.5 flex gap-3">
                <x-filament::button tag="a" :href="$this->getUrlNuovaPrenotazione()" size="lg">
                    Nuova prenotazione
                </x-filament::button>
                <x-filament::button tag="a" :href="$this->getUrlCalendario()" color="gray" outlined size="lg">
                    Vedi disponibilità torri
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
