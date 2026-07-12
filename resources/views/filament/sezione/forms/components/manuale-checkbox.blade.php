@php
    $statePath = $getStatePath();
    $isChecked = (bool) $getState();
    $nomeTorre = $torre?->nome ?? 'torre selezionata';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="flex items-start gap-4 rounded-2xl p-5"
        style="{{ $isChecked
            ? 'border:1.5px solid var(--green-200);background:var(--green-50)'
            : 'border:1.5px solid var(--larch-200);background:var(--larch-100)' }}"
    >
        <x-filament::icon
            icon="heroicon-o-book-open"
            class="mt-0.5 h-6 w-6 flex-shrink-0"
            style="color:{{ $isChecked ? 'var(--green-600)' : 'var(--larch-700)' }}"
        />

        <div class="flex flex-1 flex-col gap-3">
            <div class="flex flex-col gap-1">
                <p class="text-sm font-extrabold text-gray-950 dark:text-white">
                    @if ($isChecked)
                        Manuale della {{ $nomeTorre }} confermato
                    @else
                        Prima di continuare: leggi il manuale della {{ $nomeTorre }}
                    @endif
                </p>
                <p class="text-xs leading-relaxed" style="color:var(--text-body)">
                    @if ($isChecked)
                        Se cambi torre dovrai confermare di nuovo la lettura del manuale corrispondente.
                    @else
                        È un requisito di sicurezza. Se cambi torre dovrai confermare di nuovo la lettura.
                    @endif
                </p>
            </div>

            @if ($torre && filled($torre->manuale_pdf_path))
                <a
                    href="{{ asset('storage/'.$torre->manuale_pdf_path) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex w-fit items-center gap-2 text-xs font-bold underline"
                    style="color:{{ $isChecked ? 'var(--green-700)' : 'var(--larch-700)' }}"
                >
                    <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4" />
                    Visualizza/scarica il manuale d'istruzioni della torre selezionata
                </a>
            @else
                <span class="text-xs" style="color:var(--text-muted)">Manuale non ancora disponibile.</span>
            @endif

            <label
                class="flex cursor-pointer items-center gap-3 rounded-xl p-3.5"
                style="background:var(--surface-card);border:1.5px solid {{ $isChecked ? 'var(--green-200)' : 'var(--larch-200)' }}"
            >
                <x-filament::input.checkbox
                    :valid="! $errors->has($statePath)"
                    :attributes="
                        \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                            ->merge([
                                'disabled' => $isDisabled(),
                                'id' => $getId(),
                                $applyStateBindingModifiers('wire:model') => $statePath,
                            ], escape: false)
                            ->class(['h-[22px] w-[22px]'])
                    "
                />
                <span class="text-sm font-bold text-gray-950 dark:text-white">
                    Dichiaro di aver letto il manuale d'istruzioni della torre selezionata
                </span>
            </label>
        </div>
    </div>
</x-dynamic-component>
