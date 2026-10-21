@props([
    'label' => null,
    'help' => null,
    'error' => null,
    'icon' => null,
    'optional' => false,
    'size' => null,
    'multiline' => false,
    'id' => null,
])
@php
    // Mirror of design/website/src/components/forms/Input.jsx.
    $fid = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
    $tag = $multiline ? 'textarea' : 'input';
    $inputClasses = array_filter([
        'd-input',
        $icon ? 'd-input--lead' : null,
        isset($trailing) ? 'd-input--trail' : null,
        $size === 'sm' ? 'd-input--sm' : null,
    ]);
@endphp
<div {{ $attributes->only(['class', 'style'])->class(['d-field']) }}>
    @if ($label)
        <label class="d-label" for="{{ $fid }}">{{ $label }}@if ($optional)<span class="d-label__opt"> · {{ __('ui.optional') }}</span>@endif</label>
    @endif
    <div class="d-control" @if ($attributes->has('dir')) dir="{{ $attributes->get('dir') }}" @endif>
        @if ($icon)<span class="d-control__lead"><x-icon :name="$icon" size="18" /></span>@endif
        <{{ $tag }}
            id="{{ $fid }}"
            {{ $attributes->except(['class', 'style'])->class($inputClasses) }}
            @if ($error) aria-invalid="true" @endif
        >@if ($multiline){{ $slot ?? '' }}</textarea>@endif
        @isset($trailing)<span class="d-control__trail">{{ $trailing }}</span>@endisset
    </div>
    @if ($error)
        <span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $error }}</span>
    @elseif ($help)
        <span class="d-help">{{ $help }}</span>
    @endif
</div>
