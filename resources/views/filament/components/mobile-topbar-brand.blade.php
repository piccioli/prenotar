{{-- Shell mobile (US-002): logo/nome pannello nella topbar, visibili solo sotto 768px.
     Filament mostra il brand solo nella sidebar quando la navigazione è laterale, quindi
     a sidebar chiusa (mobile) la topbar avrebbe altrimenti solo l'hamburger. --}}
<div class="me-auto flex min-w-0 items-center gap-2 md:hidden">
    <x-filament-panels::logo />
    <span class="truncate text-sm font-extrabold text-gray-950 dark:text-white">
        {{ filament()->getBrandName() }}
    </span>
</div>
