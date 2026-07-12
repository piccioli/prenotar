<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div class="flex flex-col gap-y-6">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        @php
            $torri = $this->getTableRecords();
            $toggleAction = $this->getTable()->getAction('toggle_active');
        @endphp

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @forelse ($torri as $torre)
                @php
                    $colore = \App\Models\Torre::coloreHexPer($torre);
                    $documenti = [
                        $torre->specs_tecniche_pdf_path ? ['icon' => 'heroicon-o-document-text', 'label' => 'Specifiche tecniche.pdf'] : null,
                        $torre->manuale_pdf_path ? ['icon' => 'heroicon-o-book-open', 'label' => 'Manuale d\'istruzioni.pdf'] : null,
                        $torre->foto_path ? ['icon' => 'heroicon-o-photo', 'label' => 'Foto'] : null,
                    ];
                    $documenti = array_filter($documenti);
                @endphp

                <div
                    class="flex flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-900"
                    style="border-color:var(--border-subtle)"
                >
                    <div class="h-2" style="background-color:{{ $colore }}"></div>

                    <div class="flex flex-1 flex-col gap-4 p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-3 w-3 flex-shrink-0 rounded" style="background-color:{{ $colore }}"></span>
                                    <span class="text-lg font-extrabold tracking-tight text-gray-950 dark:text-white">
                                        {{ $torre->nome }}
                                    </span>
                                </div>
                                @if ($torre->descrizione)
                                    <p class="text-sm leading-relaxed" style="color:var(--stone-600)">
                                        {{ $torre->descrizione }}
                                    </p>
                                @endif
                            </div>
                            <x-filament::badge :color="$torre->is_active ? 'success' : 'danger'">
                                {{ $torre->is_active ? 'Attiva' : 'Disattivata' }}
                            </x-filament::badge>
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-xl border px-4 py-3.5"
                            style="background-color:color-mix(in srgb, {{ $colore }} 10%, white); border-color:color-mix(in srgb, {{ $colore }} 35%, white)"
                        >
                            <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5 flex-shrink-0" style="color:{{ $colore }}" />
                            <div class="flex flex-col gap-0.5">
                                <p class="text-xs font-extrabold uppercase tracking-wide" style="color:{{ $colore }}">
                                    Indirizzo di deposito
                                </p>
                                <p class="text-[15px] font-bold text-gray-950 dark:text-white">
                                    {{ $torre->indirizzo_deposito }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @forelse ($documenti as $documento)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-2 text-[13px] font-bold"
                                    style="border-color:var(--border-default); color:var(--stone-700)"
                                >
                                    <x-filament::icon :icon="$documento['icon']" class="h-[15px] w-[15px]" style="color:{{ $colore }}" />
                                    {{ $documento['label'] }}
                                </span>
                            @empty
                                <span class="text-xs" style="color:var(--text-muted)">Nessun documento caricato.</span>
                            @endforelse
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t pt-4" style="border-color:var(--border-subtle)">
                            <div class="flex items-center gap-2">
                                <x-filament::button
                                    tag="a"
                                    :href="\App\Filament\Admin\Resources\TorreResource::getUrl('edit', ['record' => $torre])"
                                    color="gray"
                                    outlined
                                    size="sm"
                                >
                                    Modifica
                                </x-filament::button>

                                @if ($toggleAction)
                                    <x-filament-tables::actions :actions="[$toggleAction]" :record="$torre" />
                                @endif
                            </div>
                            <span class="text-xs font-semibold" style="color:var(--stone-500)">
                                {{ $torre->prenotazioni_count }} {{ $torre->prenotazioni_count === 1 ? 'prenotazione' : 'prenotazioni' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="col-span-full flex flex-col items-center gap-2 rounded-2xl border border-dashed p-10 text-center"
                    style="border-color:var(--border-default)"
                >
                    <x-filament::icon
                        :icon="$this->getTable()->getEmptyStateIcon()"
                        class="h-8 w-8"
                        style="color:var(--text-muted)"
                    />
                    <p class="text-sm font-extrabold text-gray-950 dark:text-white">
                        {{ $this->getTable()->getEmptyStateHeading() }}
                    </p>
                    <p class="text-xs" style="color:var(--text-muted)">
                        {{ $this->getTable()->getEmptyStateDescription() }}
                    </p>
                </div>
            @endforelse
        </div>

        @if (
            ($torri instanceof \Illuminate\Contracts\Pagination\Paginator)
            && ((! ($torri instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) || $torri->total())
        )
            <x-filament::pagination
                :extreme-links="$this->getTable()->hasExtremePaginationLinks()"
                :page-options="$this->getTable()->getPaginationPageOptions()"
                :paginator="$torri"
            />
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
