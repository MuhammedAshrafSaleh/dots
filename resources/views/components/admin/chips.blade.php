@props([
    'label' => null,
    'model' => null,          // wire:model path holding the selected array
    'options' => [],          // list of ['value' => ..., 'label' => ...] (or scalars)
    'selected' => [],         // current values, for the server-rendered active state
])
{{-- Mirror of admin-kit.jsx AChips: multi-select as toggle chips. Toggling
     calls toggleChip() (InteractsWithAdminForms trait) so the array stays
     server-authoritative. --}}
<div class="d-field">
    @if ($label)<span class="d-label">{{ $label }}</span>@endif
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        @foreach ($options as $opt)
            @php
                $value = is_array($opt) ? ($opt['value'] ?? null) : $opt;
                $text = is_array($opt) ? ($opt['label'] ?? $value) : $opt;
                $on = in_array($value, (array) $selected, true);
                $arg = is_numeric($value) ? $value : "'".e($value)."'";
            @endphp
            <button type="button" class="d-chip {{ $on ? 'd-chip--active' : '' }}"
                    aria-pressed="{{ $on ? 'true' : 'false' }}"
                    wire:click="toggleChip('{{ $model }}', {{ $arg }})">
                @if ($on)<x-icon name="check" size="14" stroke="2" />@endif{{ $text }}
            </button>
        @endforeach
    </div>
</div>
