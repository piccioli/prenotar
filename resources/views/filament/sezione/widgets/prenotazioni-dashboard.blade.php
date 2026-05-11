<x-filament-widgets::widget>
    <x-filament::section>
        @php $attiva = $this->getPrenotazioneAttiva() @endphp

        @if($attiva)
            <x-slot name="heading">Prenotazione attiva</x-slot>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $attiva->nome_evento }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $attiva->data_inizio_prenotazione->format('d/m/Y') }}
                        →
                        {{ $attiva->data_fine_prenotazione->format('d/m/Y') }}
                        @if($attiva->torre)
                            — {{ $attiva->torre->nome }}
                        @endif
                    </p>
                    <p class="mt-1">
                        <x-filament::badge :color="match($attiva->status) {
                            \App\Enums\PrenotazioneStatus::Inviata => 'warning',
                            \App\Enums\PrenotazioneStatus::Approvata => 'success',
                            \App\Enums\PrenotazioneStatus::InviatoPdfFirmato => 'info',
                            \App\Enums\PrenotazioneStatus::InviatoAssicurazione => 'primary',
                            default => 'gray',
                        }">
                            {{ $attiva->status->label() }}
                        </x-filament::badge>
                    </p>
                </div>
                <div class="flex gap-2">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        tag="a"
                        :href="$this->getUrlListaPrenotazioni()"
                    >
                        Tutte le prenotazioni
                    </x-filament::button>
                </div>
            </div>
        @else
            <x-slot name="heading">Nessuna prenotazione attiva</x-slot>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Non hai prenotazioni in corso. Crea una nuova prenotazione per richiedere l'uso di una torre di arrampicata.
                </p>
                <x-filament::button
                    size="sm"
                    tag="a"
                    :href="$this->getUrlNuovaPrenotazione()"
                >
                    Crea prenotazione
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
