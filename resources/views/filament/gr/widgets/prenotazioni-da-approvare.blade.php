<div class="space-y-4">
    {{-- Card statistiche - desktop --}}
    <div class="hidden gap-4 md:grid md:grid-cols-2" style="max-width:760px">
        {{-- Card: Da approvare --}}
        <div class="flex items-center gap-4 rounded-xl border-[1.5px] bg-white p-5 dark:bg-gray-900" style="border-color:var(--larch-200)">
            <div class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl" style="background-color:var(--larch-100)">
                <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6" style="color:var(--larch-700)" />
            </div>
            <div>
                <p class="text-[30px] font-extrabold leading-none" style="color:var(--stone-900)">
                    {{ $this->getCountDaApprovare() }}
                </p>
                <p class="mt-1 text-sm font-semibold" style="color:var(--stone-600)">richieste da approvare</p>
            </div>
        </div>

        {{-- Card: Approvate prossimi 30 gg --}}
        <div class="flex items-center gap-4 rounded-xl border bg-white p-5 dark:bg-gray-900" style="border-color:var(--stone-200)">
            <div class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-2xl" style="background-color:var(--green-100)">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6" style="color:var(--green-600)" />
            </div>
            <div>
                <p class="text-[30px] font-extrabold leading-none" style="color:var(--stone-900)">
                    {{ $this->getCountApprovateProssimi30Giorni() }}
                </p>
                <p class="mt-1 text-sm font-semibold" style="color:var(--stone-600)">approvate nei prossimi 30 giorni</p>
            </div>
        </div>
    </div>

    {{-- Card statistiche - mobile --}}
    <div class="grid grid-cols-2 gap-3 md:hidden">
        <div class="rounded-2xl border-[1.5px] bg-white p-4" style="border-color:var(--larch-200)">
            <p class="text-[26px] font-extrabold leading-none" style="color:var(--stone-900)">{{ $this->getCountDaApprovare() }}</p>
            <p class="mt-1 text-[12.5px] font-semibold leading-tight" style="color:var(--stone-600)">da approvare</p>
        </div>
        <div class="rounded-2xl border bg-white p-4" style="border-color:var(--stone-200)">
            <p class="text-[26px] font-extrabold leading-none" style="color:var(--stone-900)">{{ $this->getCountApprovateProssimi30Giorni() }}</p>
            <p class="mt-1 text-[12.5px] font-semibold leading-tight" style="color:var(--stone-600)">approvate nei prossimi 30 giorni</p>
        </div>
    </div>

    @if($this->getCountDaApprovare() > 0)
        {{-- Lista richieste - desktop --}}
        <div class="hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 md:block dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-3 dark:border-white/10">
                <h3 class="text-[17px] font-extrabold" style="color:var(--stone-900)">Richieste in attesa — le più urgenti in cima</h3>
                <a href="{{ $this->getUrlListaDaApprovare() }}" class="text-sm font-semibold underline" style="color:var(--green-700)">
                    Vedi tutte
                </a>
            </div>

            <div class="divide-y" style="border-color:var(--stone-100)">
                @foreach($this->getPrenotazioniDaApprovare() as $pren)
                    @php
                        $giorni = $this->giorniMancanti($pren);
                        $urgente = $this->isUrgente($pren);
                        $torreColore = \App\Models\Torre::coloreHexPer($pren->torre);
                    @endphp
                    <div class="grid grid-cols-1 items-center gap-2 px-6 py-4 md:grid-cols-[2.4fr_1.8fr_1.2fr_1.1fr_130px] md:gap-4">
                        <div class="min-w-0">
                            <p class="truncate text-[15px] font-bold" style="color:var(--stone-900)">{{ $pren->nome_evento }}</p>
                            <p class="truncate text-[13px]" style="color:var(--stone-600)">
                                @if($pren->sottosezione)
                                    @include('filament.components.etichetta-sezione', ['sottosezione' => $pren->sottosezione])
                                @else
                                    Sezione di @include('filament.components.etichetta-sezione', ['sezione' => $pren->sezione])
                                @endif
                            </p>
                        </div>

                        <p class="text-[14.5px] font-semibold" style="color:var(--stone-800)">
                            {{ $pren->data_inizio_prenotazione->format('d M') }} – {{ $pren->data_fine_prenotazione->format('d M Y') }}
                        </p>

                        <div>
                            @if($pren->torre)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide"
                                      style="background-color:color-mix(in srgb, {{ $torreColore }} 16%, white); color:{{ $torreColore }}">
                                    <span class="inline-block h-2 w-2 rounded-full" style="background-color:{{ $torreColore }}"></span>
                                    {{ $pren->torre->nome }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide" style="background-color:var(--stone-100); color:var(--stone-600)">
                                    Da assegnare
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold"
                                  style="background-color:{{ $urgente ? 'var(--larch-100)' : 'var(--stone-100)' }}; color:{{ $urgente ? 'var(--larch-700)' : 'var(--stone-600)' }}">
                                tra {{ $giorni }} giorni
                            </span>
                        </div>

                        <div class="md:text-right">
                            <x-filament::button tag="a" :href="$this->getUrlPrenotazione($pren->id)" size="sm">
                                Esamina
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lista richieste - mobile --}}
        <div class="flex flex-col gap-3 md:hidden">
            <h3 class="text-[15px] font-extrabold" style="color:var(--stone-900)">Richieste in attesa</h3>

            @foreach($this->getPrenotazioniDaApprovare() as $pren)
                @php
                    $giorni = $this->giorniMancanti($pren);
                    $urgente = $this->isUrgente($pren);
                @endphp
                <div class="flex flex-col gap-2.5 rounded-2xl border bg-white p-4" style="border-color:var(--stone-200)">
                    <div class="flex items-start justify-between gap-2.5">
                        <p class="text-[15px] font-extrabold leading-tight" style="color:var(--stone-900)">{{ $pren->nome_evento }}</p>
                        <span class="shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold"
                              style="background-color:{{ $urgente ? 'var(--larch-100)' : 'var(--stone-100)' }}; color:{{ $urgente ? 'var(--larch-700)' : 'var(--stone-600)' }}">
                            tra {{ $giorni }} gg
                        </span>
                    </div>

                    <p class="text-[13px]" style="color:var(--stone-600)">
                        @if($pren->sottosezione)
                            @include('filament.components.etichetta-sezione', ['sottosezione' => $pren->sottosezione])
                        @else
                            Sezione di @include('filament.components.etichetta-sezione', ['sezione' => $pren->sezione])
                        @endif
                        · {{ $pren->data_inizio_prenotazione->format('d M') }} – {{ $pren->data_fine_prenotazione->format('d M Y') }}
                    </p>

                    <x-filament::button tag="a" :href="$this->getUrlPrenotazione($pren->id)" size="sm" class="w-full">
                        Esamina
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border-[1.5px] border-dashed p-10 text-center" style="border-color:var(--stone-300)">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" style="background-color:var(--green-50)">
                <x-filament::icon icon="heroicon-o-check-circle" class="h-8 w-8" style="color:var(--green-600)" />
            </div>
            <h3 class="mt-4 text-lg font-extrabold" style="color:var(--stone-900)">Tutto approvato</h3>
            <p class="mx-auto mt-2 max-w-[420px] text-[14.5px]" style="color:var(--stone-600)">
                Non ci sono richieste in attesa. Ti avviseremo via email quando una Sezione invierà una nuova richiesta.
            </p>
        </div>
    @endif
</div>
