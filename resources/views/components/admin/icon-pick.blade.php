@props([
    'label' => null,
    'model' => null,    // wire:model path holding the icon name
    'value' => null,    // current value, for the server-rendered preview
])
@php
    // Mirror of admin-kit.jsx AIconPick: preview + select from the Lucide set.
    // wire:model.live keeps the preview in sync by re-rendering the parent.
    $set = config('admin_icons', []);
    $preview = $value ?: ($set[0] ?? 'lightbulb');
@endphp
<div class="d-field">
    @if ($label)<span class="d-label">{{ $label }}</span>@endif
    <div style="display: flex; gap: 8px; align-items: center;">
        <span class="a-kpi__ico" style="width: 40px; height: 40px;">
            <x-icon :name="$preview" size="18" />
        </span>
        <div style="flex: 1; min-width: 0;">
            <x-select
                size="sm"
                :options="$set"
                wire:model.live="{{ $model }}"
                :aria-label="$label"
            />
        </div>
    </div>
</div>
