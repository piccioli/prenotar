{{-- Shell mobile (US-002): icona notifiche nella topbar, come da mockup mobile-3-ruoli.dc.html.
     Disabilitata: non esiste ancora un canale di notifiche reale (fuori scope di questo restyle),
     è solo il posto riservato nel layout in cui una story futura potrà agganciare i dati veri. --}}
<div class="flex items-center md:hidden">
    <x-filament::icon-button
        color="gray"
        icon="heroicon-o-bell"
        icon-size="lg"
        label="Notifiche"
        disabled
    />
</div>
