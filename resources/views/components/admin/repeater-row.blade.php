@props([
    'index' => 0,            // position in the list (for drag handlers)
    'key' => null,           // stable key for reorder
    'remove' => null,        // Livewire method name; called with the key
    'sortable' => false,
])
{{-- A single repeater row: drag grip + fields (default slot) + remove. --}}
<div class="a-card"
     @if ($sortable)
        data-sortable-key="{{ $key }}" draggable="true"
        @dragstart="start($event, {{ $index }})" @dragover="over($event)" @drop="drop($event, {{ $index }})"
     @endif
     style="padding: 12px; display: grid; grid-template-columns: {{ $sortable ? '20px ' : '' }}minmax(0, 1fr) auto; gap: 10px; align-items: start;">
    @if ($sortable)
        <span class="a-grip" style="padding-top: 8px; cursor: grab;"><x-icon name="grip-vertical" size="16" /></span>
    @endif
    <div style="display: grid; gap: 10px; min-width: 0;">{{ $slot }}</div>
    @if ($remove)
        <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="{{ $remove }}('{{ $key }}')" aria-label="{{ __('admin.widgets.remove') }}">
            <x-icon name="trash-2" size="16" />
        </button>
    @endif
</div>
