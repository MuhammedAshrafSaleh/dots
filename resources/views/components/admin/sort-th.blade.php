@props([
    'column' => null,       // sort key
    'label' => '',
    'current' => null,      // currently sorted column
    'dir' => 'asc',
    'method' => 'sortBy',
    'num' => false,
    'shrink' => false,
])
@php
    $on = $current === $column;
    $icon = $on ? ($dir === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';
@endphp
<th {{ $attributes->class(['is-num' => $num, 'is-shrink' => $shrink]) }}
    @if ($on) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <button type="button" class="a-sort {{ $on ? 'is-on' : '' }}" wire:click="{{ $method }}('{{ $column }}')">
        {{ $label }}<x-icon :name="$icon" size="13" />
    </button>
</th>
