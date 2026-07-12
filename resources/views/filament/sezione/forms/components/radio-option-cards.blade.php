@php
    $statePath = $getStatePath();
    $id = $getId();
    $state = $getState();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="flex flex-wrap gap-3.5">
        @foreach ($getOptions() as $value => $label)
            @php
                $isSelected = $state !== null && ((string) $state === (string) $value);
            @endphp
            <label
                class="flex flex-1 cursor-pointer items-center gap-2.5 rounded-xl p-3.5"
                style="{{ $isSelected
                    ? 'border:2px solid var(--border-strong);background:var(--surface-subtle)'
                    : 'border:1.5px solid var(--border-default);background:var(--surface-card)' }}"
            >
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
                <div class="flex flex-col gap-0.5">
                    <span class="text-sm font-extrabold text-gray-950 dark:text-white">{{ $label }}</span>
                    @if ($hasDescription($value))
                        <span class="text-xs" style="color:var(--text-muted)">{{ $getDescription($value) }}</span>
                    @endif
                </div>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
