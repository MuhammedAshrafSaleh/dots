@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconEnd' => null,
    'block' => false,
    'href' => null,
    'type' => 'button',
])
@php
    // Mirror of design/website/src/components/buttons/Button.jsx.
    $isz = $size === 'sm' ? 16 : ($size === 'lg' ? 20 : 18);
    $lead = $icon ?? (in_array($variant, ['whatsapp', 'quote-outline'], true) ? 'message-circle' : null);
    $empty = trim($slot) === '';

    $classes = array_filter([
        'd-btn',
        "d-btn--{$variant}",
        $size !== 'md' ? "d-btn--{$size}" : null,
        $block ? 'd-btn--block' : null,
        $empty ? 'd-btn--icon' : null,
    ]);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($lead)<x-icon :name="$lead" :size="$isz" />@endif
        {{ $slot }}
        @if ($iconEnd)<x-icon :name="$iconEnd" :size="$isz" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($lead)<x-icon :name="$lead" :size="$isz" />@endif
        {{ $slot }}
        @if ($iconEnd)<x-icon :name="$iconEnd" :size="$isz" />@endif
    </button>
@endif
