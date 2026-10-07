@props([
    'title',
    'desc' => null,
    'meta' => null,
    'icon' => null,
    'name' => null,
    'value' => null,
    'checked' => false,
])
@php
    // Mirror of design/website/src/components/forms/RadioCard.jsx.
    $classes = array_filter(['d-radio-card', $checked ? 'd-radio-card--on' : null]);
@endphp
<label {{ $attributes->only(['class', 'style'])->class($classes) }}>
    <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($checked) {{ $attributes->except(['class', 'style']) }} style="position:absolute;opacity:0;pointer-events:none;">
    <span class="d-check"><input type="radio" readonly @checked($checked) tabindex="-1" aria-hidden="true"></span>
    <span style="flex:1;min-width:0;display:grid;gap:3px;">
        <span style="font:800 14px/1.3 var(--font-body);display:flex;align-items:center;gap:8px;">@if ($icon)<x-icon :name="$icon" size="18" />@endif{{ $title }}</span>
        @if ($desc)<span style="font:500 13px/1.45 var(--font-body);color:var(--fg-2);">{{ $desc }}</span>@endif
    </span>
    @if ($meta)<span style="font:800 14px/1.3 var(--font-body);white-space:nowrap;">{{ $meta }}</span>@endif
</label>
