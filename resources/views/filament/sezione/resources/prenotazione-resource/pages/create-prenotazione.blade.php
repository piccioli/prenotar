<x-filament-panels::page
    @class([
        'fi-resource-create-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    @if ($prenotazioneAttiva)
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <x-slot name="heading">
                Hai già una prenotazione attiva
            </x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Non puoi creare una nuova prenotazione finché
                <strong>{{ $prenotazioneAttiva->nome_evento }}</strong>
                (dal {{ $prenotazioneAttiva->data_inizio_prenotazione->format('d/m/Y') }}
                al {{ $prenotazioneAttiva->data_fine_prenotazione->format('d/m/Y') }})
                non viene conclusa o annullata.
            </p>

            <div class="mt-4">
                <x-filament::link
                    :href="\App\Filament\Sezione\Resources\PrenotazioneResource::getUrl('view', ['record' => $prenotazioneAttiva])"
                >
                    Vedi dettaglio prenotazione
                </x-filament::link>
            </div>
        </x-filament::section>
    @else
        <x-filament-panels::form
            id="form"
            :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
            wire:submit="create"
        >
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    @endif

    <x-filament-panels::page.unsaved-data-changes-alert />
</x-filament-panels::page>
