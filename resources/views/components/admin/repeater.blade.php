@props([
    'add' => null,           // Livewire method name to append a blank row
    'addLabel' => null,
    'reorder' => null,       // Livewire method receiving the new key order (optional)
    'canAdd' => true,
])
{{-- Mirror of admin-kit.jsx ARepeat. Put the rows (each an <x-admin.repeater-row>)
     in the default slot; the parent owns the array + add/remove/reorder methods. --}}
<div style="display: grid; gap: 10px;">
    <div @if ($reorder) x-data="sortable({ wire: $wire, method: '{{ $reorder }}' })" @endif style="display: grid; gap: 10px;">
        {{ $slot }}
    </div>
    @if ($add && $canAdd)
        <div>
            <x-button size="sm" variant="outline" icon="plus" wire:click="{{ $add }}">
                {{ $addLabel ?? __('admin.widgets.add_item') }}
            </x-button>
        </div>
    @endif
</div>
