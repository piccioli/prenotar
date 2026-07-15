{{--
    Fork di vendor/filament/forms/resources/views/components/wizard.blade.php (US-007):
    stesso x-data/x-init/eventi Alpine e stessa struttura desktop (header a step + footer
    Annulla/Continua), invariati carattere per carattere. Aggiunge solo markup mobile
    (md:hidden) che riusa lo STESSO stato Alpine (step/getStepIndex/getSteps) già definito
    qui sotto: nessuna logica di validazione/navigazione duplicata, vedi CreatePrenotazione::form().
--}}
@php
    $isContained = $isContained();
    $statePath = $getStatePath();
    $previousAction = $getAction('previous');
    $nextAction = $getAction('next');
    $steps = collect($getChildComponentContainer()->getComponents())
        ->filter(static fn (\Filament\Forms\Components\Wizard\Step $step): bool => $step->isVisible())
        ->values();
@endphp

<div
    wire:ignore.self
    x-cloak
    x-data="{
        step: null,

        nextStep: function () {
            let nextStepIndex = this.getStepIndex(this.step) + 1

            if (nextStepIndex >= this.getSteps().length) {
                return
            }

            this.step = this.getSteps()[nextStepIndex]

            this.autofocusFields()
            this.scroll()
        },

        previousStep: function () {
            let previousStepIndex = this.getStepIndex(this.step) - 1

            if (previousStepIndex < 0) {
                return
            }

            this.step = this.getSteps()[previousStepIndex]

            this.autofocusFields()
            this.scroll()
        },

        scroll: function () {
            this.$nextTick(() => {
                this.$refs.header.children[
                    this.getStepIndex(this.step)
                ].scrollIntoView({ behavior: 'smooth', block: 'start' })
            })
        },

        autofocusFields: function () {
            $nextTick(() =>
                this.$refs[`step-${this.step}`]
                    .querySelector('[autofocus]')
                    ?.focus(),
            )
        },

        getStepIndex: function (step) {
            let index = this.getSteps().findIndex(
                (indexedStep) => indexedStep === step,
            )

            if (index === -1) {
                return 0
            }

            return index
        },

        getSteps: function () {
            return JSON.parse(this.$refs.stepsData.value)
        },

        getStepLabel: function (step) {
            return JSON.parse(this.$refs.stepsLabels.value)[this.getStepIndex(step)] ?? ''
        },

        isFirstStep: function () {
            return this.getStepIndex(this.step) <= 0
        },

        isLastStep: function () {
            return this.getStepIndex(this.step) + 1 >= this.getSteps().length
        },

        isStepAccessible: function (stepId) {
            return (
                @js($isSkippable()) || this.getStepIndex(this.step) > this.getStepIndex(stepId)
            )
        },

        updateQueryString: function () {
            if (! @js($isStepPersistedInQueryString())) {
                return
            }

            const url = new URL(window.location.href)
            url.searchParams.set(@js($getStepQueryStringKey()), this.step)

            history.pushState(null, document.title, url.toString())
        },
    }"
    x-init="
        $watch('step', () => updateQueryString())

        step = getSteps().at({{ $getStartStep() - 1 }})

        autofocusFields()
    "
    x-on:next-wizard-step.window="if ($event.detail.statePath === '{{ $statePath }}') nextStep()"
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->merge($getExtraAlpineAttributes(), escape: false)
            ->class([
                'fi-fo-wizard',
                'fi-contained rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => $isContained,
            ])
    }}
>
    <input
        type="hidden"
        value="{{ $steps->map(static fn (\Filament\Forms\Components\Wizard\Step $step) => $step->getId())->toJson() }}"
        x-ref="stepsData"
    />

    <input
        type="hidden"
        value="{{ $steps->map(static fn (\Filament\Forms\Components\Wizard\Step $step) => $step->getLabel())->toJson() }}"
        x-ref="stepsLabels"
    />

    {{-- Mobile wizard chrome (US-007, mockup "Mobile Sezione Wizard step 1"): topbar con
         freccia indietro + step corrente, barra di avanzamento sottile. Affiancata (non
         sostitutiva) alla topbar standard di Filament (hamburger/notifiche, US-002): quella
         resta per non perdere la via di navigazione verso le altre pagine, questa vive
         appena sotto (sticky top-16, 4rem = altezza h-16 della topbar Filament). --}}
    <div class="sticky top-16 z-10 md:hidden" x-cloak>
        <div class="flex items-center gap-3 px-4 py-3.5" style="background:var(--surface-inverse)">
            <button
                type="button"
                x-on:click="previousStep()"
                x-bind:class="{ 'pointer-events-none opacity-40': isFirstStep() }"
                aria-label="Passo precedente"
            >
                <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5 text-white" />
            </button>

            <div class="flex min-w-0 flex-col">
                <span class="truncate text-[15px] font-extrabold leading-tight text-white">
                    {{ __('Nuova prenotazione') }}
                </span>
                <span
                    class="truncate text-[11px] font-medium"
                    style="color:var(--ardesia-100)"
                    x-text="'Passo ' + (getStepIndex(step) + 1) + ' di ' + getSteps().length + ' — ' + getStepLabel(step)"
                ></span>
            </div>
        </div>

        <div class="flex gap-1.5 px-4 pt-3" style="background:var(--surface-page)">
            @foreach ($steps as $step)
                <span
                    class="h-[5px] flex-1 rounded-full"
                    x-bind:style="{ background: getStepIndex(step) === {{ $loop->index }} ? 'var(--border-strong)' : 'var(--stone-300)' }"
                ></span>
            @endforeach
        </div>
    </div>

    <ol
        @if (filled($label = $getLabel()))
            aria-label="{{ $label }}"
        @endif
        role="list"
        @class([
            'fi-fo-wizard-header hidden divide-y divide-gray-200 dark:divide-white/5 md:grid md:grid-flow-col md:divide-y-0 md:overflow-x-auto',
            'border-b border-gray-200 dark:border-white/10' => $isContained,
            'rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => ! $isContained,
        ])
        x-ref="header"
    >
        @foreach ($getChildComponentContainer()->getComponents() as $step)
            <li
                class="fi-fo-wizard-header-step relative flex"
                x-bind:class="{
                    'fi-active': getStepIndex(step) === {{ $loop->index }},
                    'fi-completed': getStepIndex(step) > {{ $loop->index }},
                }"
            >
                <button
                    type="button"
                    x-bind:aria-current="getStepIndex(step) === {{ $loop->index }} ? 'step' : null"
                    x-on:click="step = @js($step->getId())"
                    x-bind:disabled="! isStepAccessible(@js($step->getId())) || @js($previousAction->isDisabled())"
                    role="step"
                    class="fi-fo-wizard-header-step-button flex h-full items-center gap-x-4 px-6 py-4 text-start"
                >
                    <div
                        class="fi-fo-wizard-header-step-icon-ctn flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        x-bind:class="{
                            'bg-primary-600 dark:bg-primary-500':
                                getStepIndex(step) > {{ $loop->index }},
                            'border-2': getStepIndex(step) <= {{ $loop->index }},
                            'border-primary-600 dark:border-primary-500':
                                getStepIndex(step) === {{ $loop->index }},
                            'border-gray-300 dark:border-gray-600':
                                getStepIndex(step) < {{ $loop->index }},
                        }"
                    >
                        @php
                            $completedIcon = $step->getCompletedIcon();
                        @endphp

                        <x-filament::icon
                            :alias="filled($completedIcon) ? null : 'forms::components.wizard.completed-step'"
                            :icon="$completedIcon ?? 'heroicon-o-check'"
                            x-cloak="x-cloak"
                            x-show="getStepIndex(step) > {{ $loop->index }}"
                            class="fi-fo-wizard-header-step-icon h-6 w-6 text-white"
                        />

                        @if (filled($icon = $step->getIcon()))
                            <x-filament::icon
                                :icon="$icon"
                                x-cloak="x-cloak"
                                x-show="getStepIndex(step) <= {{ $loop->index }}"
                                class="fi-fo-wizard-header-step-icon h-6 w-6"
                                x-bind:class="{
                                    'text-gray-500 dark:text-gray-400': getStepIndex(step) !== {{ $loop->index }},
                                    'text-primary-600 dark:text-primary-500': getStepIndex(step) === {{ $loop->index }},
                                }"
                            />
                        @else
                            <span
                                x-show="getStepIndex(step) <= {{ $loop->index }}"
                                class="fi-fo-wizard-header-step-indicator text-sm font-medium"
                                x-bind:class="{
                                    'text-gray-500 dark:text-gray-400':
                                        getStepIndex(step) !== {{ $loop->index }},
                                    'text-primary-600 dark:text-primary-500':
                                        getStepIndex(step) === {{ $loop->index }},
                                }"
                            >
                                {{ str_pad($loop->index + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endif
                    </div>

                    <div class="grid justify-items-start md:w-max md:max-w-60">
                        @if (! $step->isLabelHidden())
                            <span
                                class="fi-fo-wizard-header-step-label text-sm font-medium"
                                x-bind:class="{
                                    'text-gray-500 dark:text-gray-400':
                                        getStepIndex(step) < {{ $loop->index }},
                                    'text-primary-600 dark:text-primary-400':
                                        getStepIndex(step) === {{ $loop->index }},
                                    'text-gray-950 dark:text-white': getStepIndex(step) > {{ $loop->index }},
                                }"
                            >
                                {{ $step->getLabel() }}
                            </span>
                        @endif

                        @if (filled($description = $step->getDescription()))
                            <span
                                class="fi-fo-wizard-header-step-description text-start text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ $description }}
                            </span>
                        @endif
                    </div>
                </button>

                @if (! $loop->last)
                    <div
                        aria-hidden="true"
                        class="fi-fo-wizard-header-step-separator absolute end-0 hidden h-full w-5 md:block"
                    >
                        <svg
                            fill="none"
                            preserveAspectRatio="none"
                            viewBox="0 0 22 80"
                            class="h-full w-full text-gray-200 dark:text-white/5 rtl:rotate-180"
                        >
                            <path
                                d="M0 -2L20 40L0 82"
                                stroke-linejoin="round"
                                stroke="currentcolor"
                                vector-effect="non-scaling-stroke"
                            ></path>
                        </svg>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>

    @foreach ($getChildComponentContainer()->getComponents() as $step)
        {{ $step }}
    @endforeach

    <div
        @class([
            'hidden items-center justify-between gap-x-3 md:flex',
            'px-6 pb-6' => $isContained,
            'mt-6' => ! $isContained,
        ])
    >
        <span
            x-cloak
            @if (! $previousAction->isDisabled())
                x-on:click="previousStep"
            @endif
            x-show="! isFirstStep()"
        >
            {{ $previousAction }}
        </span>

        <span x-show="isFirstStep()">
            {{ $getCancelAction() }}
        </span>

        <span
            x-cloak
            @if (! $nextAction->isDisabled())
                x-on:click="
                    $wire.dispatchFormEvent(
                        'wizard::nextStep',
                        '{{ $statePath }}',
                        getStepIndex(step),
                    )
                "
            @endif
            x-bind:class="{ 'hidden': isLastStep(), 'block': ! isLastStep() }"
            wire:loading.class="pointer-events-none opacity-70"
        >
            {{ $nextAction }}
        </span>

        <span
            x-bind:class="{ 'hidden': ! isLastStep(), 'block': isLastStep() }"
        >
            {{ $getSubmitAction() }}
        </span>
    </div>

    {{-- CTA fissa in basso su mobile (US-007): stesso evento Alpine/Livewire del footer
         desktop sopra (dispatchFormEvent('wizard::nextStep', ...) per "Continua", submit
         nativo del form per l'ultimo step), solo resa a piena larghezza e ancorata al fondo
         dello schermo. L'eventuale messaggio di blocco ($mobileNextHint, passato da
         CreatePrenotazione::form() via ->viewData(), stessa closure usata per disabilitare
         "Continua": nessuna logica duplicata) compare sotto al pulsante solo quando pertinente. --}}
    <div
        class="fixed inset-x-0 bottom-0 z-30 flex flex-col gap-2 border-t px-4 pb-[max(0.875rem,env(safe-area-inset-bottom))] pt-3.5 md:hidden"
        style="background:var(--surface-page);border-color:var(--border-subtle)"
    >
        <span
            x-cloak
            @if (! $nextAction->isDisabled())
                x-on:click="
                    $wire.dispatchFormEvent(
                        'wizard::nextStep',
                        '{{ $statePath }}',
                        getStepIndex(step),
                    )
                "
            @endif
            x-bind:class="{ 'hidden': isLastStep(), 'block': ! isLastStep() }"
            wire:loading.class="pointer-events-none opacity-70"
        >
            <x-filament::button
                :color="$nextAction->getColor() ?? 'primary'"
                :disabled="$nextAction->isDisabled()"
                class="w-full justify-center"
            >
                {{ $nextAction->getLabel() }}
            </x-filament::button>
        </span>

        <span x-bind:class="{ 'hidden': ! isLastStep(), 'block': isLastStep() }">
            <x-filament::button type="submit" class="w-full justify-center">
                {{ $mobileSubmitLabel ?? $nextAction->getLabel() }}
            </x-filament::button>
        </span>

        @if (filled($mobileNextHint ?? null))
            <p
                x-cloak
                x-show="! isLastStep()"
                class="text-center text-xs font-semibold"
                style="color:var(--larch-700)"
            >
                {{ $mobileNextHint }}
            </p>
        @endif
    </div>
</div>
