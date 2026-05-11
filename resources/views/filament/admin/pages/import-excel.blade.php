<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Carica file Excel">
            <x-filament-panels::form wire:submit="submit">
                {{ $this->form }}

                <x-filament::button type="submit" class="mt-4">
                    Avvia import
                </x-filament::button>
            </x-filament-panels::form>
        </x-filament::section>

        <x-filament::section heading="Ultimi 10 import">
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
