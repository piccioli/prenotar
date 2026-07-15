@props(['erroriRecenti'])

{{-- US-026 (mockup "Mobile Admin Dashboard"): pallino di stato sistema nella topbar, affiancato
     (non sostitutivo) all'icona notifiche di US-002 — stesso approccio "chrome mobile-only
     scoped a una sola pagina" già consolidato in US-007/US-021. Colore riusa la stessa soglia
     "erroriRecenti > 0" già usata dalla card "Errori recenti" del widget desktop (US-025). --}}
<div class="flex items-center md:hidden">
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide"
        style="background-color:{{ $erroriRecenti > 0 ? 'var(--larch-100)' : 'var(--green-100)' }}; color:{{ $erroriRecenti > 0 ? 'var(--larch-700)' : 'var(--green-700)' }}"
    >
        <span class="h-1.5 w-1.5 rounded-full" style="background-color:{{ $erroriRecenti > 0 ? 'var(--larch-600)' : 'var(--green-600)' }}"></span>
        {{ $erroriRecenti > 0 ? __('Attenzione') : __('OK') }}
    </span>
</div>
