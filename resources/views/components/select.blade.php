@props([
    'label' => null,
    'help' => null,
    'error' => null,
    'options' => [],
    'placeholder' => null,
    'selected' => null,
    'size' => null,
    'id' => null,
])
@php
    // Mirror of design/website/src/components/forms/Select.jsx.
    // $options: list of scalars, [value => label], or list of ['value','label','disabled'].
    $fid = $id ?? 'field-'.\Illuminate\Support\Str::random(8);
    $normalized = [];
    foreach ($options as $key => $opt) {
        if (is_array($opt)) {
            $normalized[] = ['value' => $opt['value'] ?? $key, 'label' => $opt['label'] ?? $opt['value'] ?? $key, 'disabled' => $opt['disabled'] ?? false];
        } elseif (is_string($key)) {
            $normalized[] = ['value' => $key, 'label' => $opt, 'disabled' => false];
        } else {
            $normalized[] = ['value' => $opt, 'label' => $opt, 'disabled' => false];
        }
    }
    $selectClasses = array_filter(['d-input', 'd-select', $size === 'sm' ? 'd-input--sm' : null]);
@endphp
<div {{ $attributes->only(['class', 'style'])->class(['d-field']) }}>
    @if ($label)<label class="d-label" for="{{ $fid }}">{{ $label }}</label>@endif
    <div class="d-control">
        <select id="{{ $fid }}" {{ $attributes->except(['class', 'style'])->class($selectClasses) }} @if ($error) aria-invalid="true" @endif>
            @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($normalized as $o)
                <option value="{{ $o['value'] }}" @disabled($o['disabled']) @selected($selected !== null && (string) $selected === (string) $o['value'])>{{ $o['label'] }}</option>
            @endforeach
        </select>
        <span class="d-control__trail"><x-icon name="chevron-down" size="18" /></span>
    </div>
    @if ($error)
        <span class="d-help d-help--error">{{ $error }}</span>
    @elseif ($help)
        <span class="d-help">{{ $help }}</span>
    @endif
</div>
