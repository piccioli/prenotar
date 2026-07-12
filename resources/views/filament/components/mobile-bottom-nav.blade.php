@props(['items'])

{{-- Shell mobile (US-002): bottom tab bar fissa, 4 voci per pannello (config passata
     dal *PanelProvider), attiva solo sotto 768px. Le pagine che necessitano di un FAB
     (es. US-013) possono aggiungerlo con un altro renderHook(BODY_END, ..., scopes: [...])
     scoped alla propria pagina, senza toccare questo componente condiviso. --}}
<nav
    class="fi-mobile-bottom-nav fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-gray-200 bg-white pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 dark:border-white/10 dark:bg-gray-900 md:hidden"
>
    @foreach ($items as $item)
        <a
            href="{{ $item['url'] }}"
            @class([
                'flex min-h-[44px] flex-col items-center justify-center gap-0.5 py-1.5 text-[10.5px]',
                'font-extrabold text-[color:var(--text-brand)]' => $item['active'],
                'font-semibold text-gray-500 dark:text-gray-400' => ! $item['active'],
            ])
        >
            <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
