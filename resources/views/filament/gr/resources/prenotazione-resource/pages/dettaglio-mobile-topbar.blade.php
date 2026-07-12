@props(['backUrl'])

{{-- US-021 (mockup "Mobile GR Dettaglio"): barra sticky mobile-only con freccia indietro
     verso la lista + etichetta generica "Dettaglio richiesta", affiancata (non sostitutiva)
     alla topbar standard di Filament (hamburger/notifiche, US-002) — stesso pattern
     "sticky top-16, md:hidden" già consolidato per il wizard Sezione (US-007). --}}
<div
    class="sticky top-16 z-10 flex items-center gap-3 px-4 py-3.5 md:hidden"
    style="background:var(--surface-inverse)"
>
    <a href="{{ $backUrl }}" aria-label="{{ __('Torna alla lista') }}" class="flex h-11 w-11 items-center justify-center">
        <x-filament::icon icon="heroicon-o-arrow-left" class="h-5 w-5 text-white" />
    </a>
    <span class="truncate text-[15px] font-extrabold text-white">
        {{ __('Dettaglio richiesta') }}
    </span>
</div>
