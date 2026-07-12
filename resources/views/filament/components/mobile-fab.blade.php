@props(['href', 'icon' => 'heroicon-o-plus', 'label' => 'Azione rapida'])

{{-- FAB per singole pagine mobile (US-013 e successive, vedi commento in
     mobile-bottom-nav.blade.php): fixed sopra la bottom tab bar condivisa
     (che riserva 4.5rem in .fi-main, US-002), mai sovrapposta. Da aggiungere
     pagina per pagina via renderHook(BODY_END, ..., scopes: [...]), non qui. --}}
<a
    href="{{ $href }}"
    aria-label="{{ $label }}"
    class="fi-mobile-fab fixed z-20 flex h-14 w-14 items-center justify-center rounded-full shadow-lg md:hidden"
    style="right:1.125rem;bottom:5.25rem;background:var(--surface-brand)"
>
    <x-filament::icon :icon="$icon" class="h-6 w-6 text-white" />
</a>
