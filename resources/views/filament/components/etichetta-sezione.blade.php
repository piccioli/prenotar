@props(['sezione' => null, 'sottosezione' => null])

@if ($sottosezione)
    <span class="font-extrabold text-gray-950 dark:text-white">{{ $sottosezione->nominativo }}</span>
    <span class="font-semibold" style="color:var(--text-muted)">(sez. rif. {{ $sottosezione->sezione?->nominativo }})</span>
@else
    <span class="font-extrabold text-gray-950 dark:text-white">{{ $sezione?->nominativo }}</span>
@endif
