@props(['richiedenteHtml', 'status', 'dataRichiesta'])

<span class="inline-flex flex-wrap items-center gap-3">
    <span>{!! $richiedenteHtml !!}</span>
    <x-filament::badge :color="$status->color()">{{ $status->label() }}</x-filament::badge>
    @if ($dataRichiesta)
        <span class="text-base text-gray-500 dark:text-gray-400">richiesta il {{ $dataRichiesta->translatedFormat('d F Y') }}</span>
    @endif
</span>
