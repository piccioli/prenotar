@props(['items'])

<div class="flex max-w-[860px] flex-col rounded-[20px] border px-8 py-7" style="background:var(--surface-card);border-color:var(--border-subtle)">
    @forelse ($items as $item)
        <div class="flex gap-4">
            <div class="flex flex-col items-center">
                <span
                    class="flex h-[34px] w-[34px] flex-shrink-0 items-center justify-center rounded-full"
                    style="background:var(--{{ $item['color'] }}-100)"
                >
                    <span class="h-4 w-4" style="color:var(--{{ $item['color'] }}-600)">
                        {{ svg($item['icon'], 'h-4 w-4') }}
                    </span>
                </span>
                @if (! $loop->last)
                    <span class="w-0.5 flex-1" style="background:var(--border-subtle)"></span>
                @endif
            </div>
            <div class="flex flex-col gap-1 {{ $loop->last ? '' : 'pb-6' }}">
                <div class="text-[15px] font-extrabold" style="color:var(--text-strong)">{{ $item['title'] }}</div>
                <div class="text-[13.5px]" style="color:var(--text-muted)">{{ $item['author'] }} · {{ $item['date'] }}</div>
                @if (filled($item['note']))
                    <div class="mt-1.5 rounded-[10px] border px-3.5 py-2.5 text-[13.5px]" style="color:var(--text-body);background:var(--surface-muted);border-color:var(--border-subtle)">
                        {{ $item['note'] }}
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p style="color:var(--text-muted)">Nessuna transizione registrata.</p>
    @endforelse
</div>
