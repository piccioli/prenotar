@php
    /** @var list<array{icon: string, titolo: string, stepId: string, righe: list<array<string, mixed>>}> $gruppi */
@endphp

<div class="flex flex-col gap-5">
    <div class="flex flex-col gap-1">
        <h3 class="text-lg font-extrabold tracking-tight text-gray-950 dark:text-white">Controlla e salva la richiesta</h3>
        <p class="text-sm" style="color:var(--text-body)">Verifica i dati inseriti: puoi modificare ogni sezione prima di salvare.</p>
    </div>

    @foreach ($gruppi as $gruppo)
        <div class="flex flex-col gap-3.5 rounded-2xl p-5" style="border:1.5px solid var(--border-default)">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 text-[15px] font-extrabold text-gray-950 dark:text-white">
                    <x-filament::icon :icon="$gruppo['icon']" class="h-[19px] w-[19px]" style="color:var(--text-brand)" />
                    {{ $gruppo['titolo'] }}
                </div>

                <button
                    type="button"
                    x-on:click="step = @js($gruppo['stepId']); $nextTick(() => scroll())"
                    class="whitespace-nowrap text-[13.5px] font-bold underline"
                    style="color:var(--text-brand)"
                >
                    Modifica
                </button>
            </div>

            <div class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[220px_1fr]">
                @foreach ($gruppo['righe'] as $riga)
                    <div style="color:var(--text-muted)">{{ $riga['label'] }}</div>

                    <div class="font-semibold text-gray-950 dark:text-white">
                        @if ($riga['tipo'] === 'torre')
                            @if ($riga['torre'] === null)
                                <span class="font-normal" style="color:var(--text-muted)">Nessuna preferenza</span>
                            @else
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background-color:{{ \App\Models\Torre::coloreHexPer($riga['torre']) }}"></span>
                                    {{ $riga['torre']->nome }}
                                </span>
                            @endif
                        @elseif ($riga['tipo'] === 'manuale')
                            @if ($riga['torre'] === null)
                                <span class="font-normal" style="color:var(--text-muted)">Non richiesto (nessuna torre selezionata)</span>
                            @elseif ($riga['confermato'])
                                <span class="inline-flex items-center gap-1.5" style="color:var(--green-700)">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4" />
                                    Lettura confermata
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5" style="color:var(--larch-700)">
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4" />
                                    Da confermare
                                </span>
                            @endif
                        @else
                            {{ $riga['valore'] }}
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
