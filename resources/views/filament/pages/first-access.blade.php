<x-filament-panels::page>
    <div class="max-w-md mx-auto">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white mb-2">Imposta il tuo indirizzo email di contatto</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Il tuo account è stato creato con un indirizzo email sintetico.
                Per ricevere correttamente tutte le notifiche di Prenotar,
                inserisci la tua email reale di contatto.
            </p>
        </div>

        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full mt-4">
                Salva e continua
            </x-filament::button>
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
