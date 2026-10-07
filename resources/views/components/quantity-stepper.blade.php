@props([
    'value' => 1,
    'min' => 1,
    'max' => 999,
    'size' => null,
    'disabled' => false,
    'name' => null,
])
@php
    // Mirror of design/website/src/components/forms/QuantityStepper.jsx. Self-contained via Alpine.
    $isz = $size === 'sm' ? 14 : 16;
    $classes = array_filter([
        'd-stepper',
        $size === 'sm' ? 'd-stepper--sm' : null,
        $disabled ? 'd-stepper--disabled' : null,
    ]);
@endphp
<div
    x-data="{ qty: {{ (int) $value }}, min: {{ (int) $min }}, max: {{ (int) $max }}, set(n){ this.qty = Math.max(this.min, Math.min(this.max, n || this.min)); } }"
    {{ $attributes->class($classes) }}
>
    <button type="button" @click="set(qty - 1)" :disabled="{{ $disabled ? 'true' : 'qty <= min' }}" aria-label="{{ __('ui.decrease_quantity') }}">
        <x-icon name="minus" :size="$isz" stroke="2" />
    </button>
    <input type="number" inputmode="numeric" x-model.number="qty" @change="set(qty)" @if ($name) name="{{ $name }}" @endif @disabled($disabled) aria-label="{{ __('ui.quantity') }}">
    <button type="button" @click="set(qty + 1)" :disabled="{{ $disabled ? 'true' : 'qty >= max' }}" aria-label="{{ __('ui.increase_quantity') }}">
        <x-icon name="plus" :size="$isz" stroke="2" />
    </button>
</div>
