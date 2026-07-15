<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div class="flex flex-col gap-y-6">
        <p class="flex items-center gap-1.5 text-sm" style="color:var(--stone-600)">
            <x-filament::icon icon="heroicon-o-building-library" class="h-4 w-4 flex-shrink-0" style="color:var(--text-brand)" />
            @include('filament.components.etichetta-sezione', ['sezione' => auth()->user()->sezione, 'sottosezione' => auth()->user()->sottosezione])
        </p>

        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{-- Desktop: tabella Filament invariata (US-012), solo nascosta sotto md. --}}
        <div class="hidden md:block">
            {{ $this->table }}
        </div>

        {{-- Mobile (US-013): stesse tab/ordinamento/paginazione della tabella sopra,
             lette via $this->getTableRecords() (stessa cache, nessuna query duplicata),
             presentate come card invece che righe di tabella. --}}
        <div class="flex flex-col gap-3 md:hidden">
            @php $mobileRecords = $this->getTableRecords(); @endphp

            @forelse ($mobileRecords as $record)
                <a
                    href="{{ \App\Filament\Sezione\Resources\PrenotazioneResource::getUrl('view', ['record' => $record]) }}"
                    class="flex flex-col gap-2.5 rounded-2xl border bg-white p-4 dark:bg-gray-900"
                    style="border-color:var(--border-subtle)"
                >
                    <div class="flex items-start justify-between gap-2.5">
                        <span class="text-[15px] font-extrabold leading-tight text-gray-950 dark:text-white">
                            {{ $record->nome_evento }}
                        </span>

                        <x-filament::icon
                            icon="heroicon-o-chevron-right"
                            class="h-[18px] w-[18px] flex-shrink-0"
                            style="color:var(--stone-400)"
                        />
                    </div>

                    <span class="text-[13px]" style="color:var(--stone-600)">
                        @if ($record->data_inizio_prenotazione->isSameDay($record->data_fine_prenotazione))
                            {{ $record->data_inizio_prenotazione->translatedFormat('d M Y') }}
                        @else
                            {{ $record->data_inizio_prenotazione->translatedFormat('d M') }} – {{ $record->data_fine_prenotazione->translatedFormat('d M Y') }}
                        @endif
                    </span>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::badge :color="$record->status->color()">
                            {{ $record->status->label() }}
                        </x-filament::badge>

                        <x-filament::badge :color="\Filament\Support\Colors\Color::hex(\App\Models\Torre::coloreHexPer($record->torre))">
                            {{ $record->torre?->nome ?? 'Da assegnare' }}
                        </x-filament::badge>
                    </div>
                </a>
            @empty
                <div
                    class="flex flex-col items-center gap-2 rounded-2xl border border-dashed p-10 text-center"
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

            @if (
                ($mobileRecords instanceof \Illuminate\Contracts\Pagination\Paginator)
                && ((! ($mobileRecords instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)) || $mobileRecords->total())
            )
                <x-filament::pagination
                    :extreme-links="$this->getTable()->hasExtremePaginationLinks()"
                    :page-options="$this->getTable()->getPaginationPageOptions()"
                    :paginator="$mobileRecords"
                />
            @endif
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
