@props([
    'icon',
    'label',
    'variant' => 'ghost',
    'size' => 'md',
    'count' => null,
])
@php
    // Mirror of design/website/src/components/buttons/IconButton.jsx.
    $isz = $size === 'sm' ? 18 : ($size === 'lg' ? 22 : 20);
    $classes = array_filter([
        'd-btn',
        "d-btn--{$variant}",
        'd-btn--icon',
        $size !== 'md' ? "d-btn--{$size}" : null,
        $count !== null ? 'd-btn--has-count' : null,
    ]);
@endphp
<button type="button" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
    <x-icon :name="$icon" :size="$isz" />
    @if ($count !== null && $count > 0)
        <span class="d-btn__count">{{ $count }}</span>
    @endif
</button>
