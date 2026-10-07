@props([
    'facet' => null,
    'active' => false,
    'removable' => false,
])
@php
    // Mirror of design/website/src/components/badges/Chip.jsx.
    // Removable (active-filter) chips render as a span wrapping a remove button;
    // plain filter chips render as a button.
    $classes = array_filter(['d-chip', $active ? 'd-chip--active' : null]);
@endphp
<{{ $removable ? 'span' : 'button' }} @if (! $removable) type="button" @endif {{ $attributes->class($classes) }}>
    @if ($facet)<span class="d-chip__key">{{ $facet }}:</span>@endif
    {{ $slot }}
    @if ($removable)
        <button type="button" class="d-chip__x" aria-label="{{ __('ui.remove_filter') }}">
            <x-icon name="x" size="14" stroke="2" />
        </button>
    @endif
</{{ $removable ? 'span' : 'button' }}>
