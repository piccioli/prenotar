<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div class="flex flex-col gap-y-6">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{-- Desktop: tabella Filament invariata (US-027), solo nascosta sotto md. --}}
        <div class="hidden md:block">
            {{ $this->table }}
        </div>

        {{-- Mobile (US-028): stessa collection/ordinamento/paginazione della tabella
             sopra, letta via $this->getTableRecords() (nessuna query duplicata),
             presentata come card. Le azioni di riga (Impersona/Reset/Attiva-Disattiva)
             sono le stesse istanze registrate in UserResource::table(), rese via
             <x-filament-tables::actions> esattamente come la tabella desktop. --}}
        <div class="flex flex-col gap-3 md:hidden">
            @php
                $mobileRecords = $this->getTableRecords();
                $rowActions = $this->getTable()->getActions();
            @endphp

            @forelse ($mobileRecords as $record)
                <div
                    class="flex flex-col gap-3 rounded-2xl border bg-white p-4 dark:bg-gray-900"
                    style="border-color:var(--border-subtle)"
                >
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[15px] font-extrabold leading-tight text-gray-950 dark:text-white">
                                {{ $record->name }}
                            </span>
                            <span class="text-[13px]" style="color:var(--stone-600)">
                                {{ $record->email }}
                            </span>
                        </div>

                        <a
                            href="{{ \App\Filament\Admin\Resources\UserResource::getUrl('edit', ['record' => $record]) }}"
                            class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full"
                            style="color:var(--stone-400)"
                        >
                            <x-filament::icon icon="heroicon-o-chevron-right" class="h-[18px] w-[18px]" />
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::badge :color="\App\Filament\Admin\Resources\UserResource::ruoloColor(\App\Filament\Admin\Resources\UserResource::ruoloLabel($record))">
                            {{ \App\Filament\Admin\Resources\UserResource::ruoloLabel($record) }}
                        </x-filament::badge>

                        <span class="text-[13px]" style="color:var(--stone-600)">
                            {!! \App\Filament\Admin\Resources\UserResource::appartenenzaLabel($record) !!}
                        </span>
                    </div>

                    {!! \App\Filament\Admin\Resources\UserResource::statoLabel($record) !!}

                    <div class="flex flex-wrap items-center gap-3 border-t pt-3" style="border-color:var(--border-subtle)">
                        <x-filament-tables::actions :actions="$rowActions" :record="$record" wrap="-sm" />
                    </div>
                </div>
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
