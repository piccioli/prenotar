@props(['attivo', 'motivo' => null])
<div style="display:flex;flex-direction:column;gap:2px">
    <span style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:{{ $attivo ? 'var(--green-700)' : 'var(--stone-500)' }}">
        <span style="width:8px;height:8px;border-radius:999px;background:{{ $attivo ? 'var(--green-600)' : 'var(--stone-400)' }}"></span>
        {{ $attivo ? 'Attivo' : 'Disattivato' }}
    </span>
    @if (! $attivo && $motivo)
        <span style="font-size:12px;color:var(--stone-500);font-style:italic">&ldquo;{{ $motivo }}&rdquo;</span>
    @endif
</div>
