@props([
    'label' => null,
    'count' => null,
    'row' => false,
    'disabled' => false,
    'checked' => false,
])
@php
    // Mirror of design/website/src/components/forms/Checkbox.jsx.
    $classes = array_filter([
        'd-check',
        $row ? 'd-check--row' : null,
        $disabled ? 'd-check--disabled' : null,
    ]);
@endphp
<label {{ $attributes->only(['class', 'style'])->class($classes) }}>
    <input type="checkbox" {{ $attributes->except(['class', 'style']) }} @checked($checked) @disabled($disabled)>
    <span>{{ $label ?? $slot }}</span>
    @if ($count !== null)<span class="d-check__count">{{ $count }}</span>@endif
</label>
