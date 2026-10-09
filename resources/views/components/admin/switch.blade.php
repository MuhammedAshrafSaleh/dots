@props([
    'label' => null,
    'help' => null,
    'disabled' => false,
])
{{-- Mirror of admin-kit.jsx ASwitch. Bind with wire:model (or x-model). --}}
<label {{ $attributes->only(['class', 'style'])->class(['a-switch']) }}>
    <input
        type="checkbox"
        role="switch"
        @disabled($disabled)
        {{ $attributes->except(['class', 'style']) }}
    >
    @if ($label || $help)
        <span>{{ $label }}@if ($help)<small>{{ $help }}</small>@endif</span>
    @endif
</label>
