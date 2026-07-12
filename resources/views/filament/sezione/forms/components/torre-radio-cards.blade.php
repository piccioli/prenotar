@php
    $statePath = $getStatePath();
    $id = $getId();
    $state = $getState();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
        @foreach ($getOptions() as $value => $label)
            @php
                $torre = $value !== '' ? \App\Models\Torre::find($value) : null;
                $isSelected = $state !== null && ((string) $state === (string) $value);
            @endphp
            <label
                class="flex cursor-pointer flex-col gap-2.5 rounded-2xl p-4"
                style="{{ $isSelected
                    ? 'border:2px solid var(--border-strong);background:var(--surface-subtle)'
                    : 'border:1.5px solid var(--border-default);background:var(--surface-card)' }}"
            >
                <div class="flex items-center gap-2.5">
                    <x-filament::input.radio
                        :valid="! $errors->has($statePath)"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                                ->merge([
                                    'disabled' => $isDisabled() || $isOptionDisabled($value, $label),
                                    'id' => $id.'-'.$value,
                                    'name' => $id,
                                    'value' => $value,
                                    $applyStateBindingModifiers('wire:model') => $statePath,
                                ], escape: false)
                        "
                    />
                    <span class="text-sm font-extrabold text-gray-950 dark:text-white">{{ $label }}</span>
                    @if ($torre)
                        <span
                            class="ml-auto h-2.5 w-2.5 flex-shrink-0 rounded-full"
                            style="background-color:{{ \App\Models\Torre::coloreHexPer($torre) }}"
                        ></span>
                    @endif
                </div>

                @if ($torre)
                    <div class="flex items-start gap-1.5 pl-[26px] text-xs leading-relaxed" style="color:var(--text-body)">
                        <x-filament::icon icon="heroicon-o-map-pin" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" style="color:var(--text-brand)" />
                        <span><span class="font-bold">Deposito:</span> {{ $torre->indirizzo_deposito }}</span>
                    </div>
                @else
                    <p class="pl-[26px] text-xs leading-relaxed" style="color:var(--text-muted)">
                        Il GR assegnerà la torre disponibile più adatta.
                    </p>
                @endif
            </label>
        @endforeach
    </div>
</x-dynamic-component>
