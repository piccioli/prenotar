@if ($bloccato)
    <div class="flex items-start gap-2.5 text-sm" style="color:var(--stone-700)">
        <x-filament::icon icon="heroicon-o-lock-closed" class="mt-0.5 h-[17px] w-[17px] flex-shrink-0" style="color:var(--larch-700)" />
        <span>L'invio si abilita quando la <strong class="font-extrabold text-gray-950 dark:text-white">delibera del consiglio</strong> è stata caricata.</span>
    </div>
@endif
