@props([
    'items',       // iterable of OrderItem (snapshot: image_url, qty)
    'size' => 52,
    'max' => 4,
])
@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\OrderItem> $items */
    $items = collect($items)->values();
    $shown = $items->take($max);
    $more = $items->count() - $max;
@endphp
<div style="display:flex;gap:8px;align-items:center;">
    @foreach ($shown as $it)
        <span style="position:relative;flex:none;">
            <span style="display:block;width:{{ $size }}px;height:{{ $size }}px;border-radius:8px;border:1px solid var(--border-1);background:var(--white) center/contain no-repeat;@if ($it->image_url) background-image:url('{{ $it->image_url }}');@endif"></span>
            @if ($it->qty > 1)<span class="d-summary__qty">{{ $it->qty }}</span>@endif
        </span>
    @endforeach
    @if ($more > 0)
        <span style="width:{{ $size }}px;height:{{ $size }}px;border-radius:8px;background:var(--ink-50);display:grid;place-items:center;font:800 13px var(--font-body);color:var(--fg-2);">+{{ $more }}</span>
    @endif
</div>
