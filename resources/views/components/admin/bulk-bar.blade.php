@props([
    'count' => 0,       // number of selected rows
    'clear' => null,    // Livewire method name to clear the selection
])
{{-- Mirror of admin-kit.jsx AList bulk bar. Put the action buttons in the
     default slot. Shown by the parent only when rows are selected. --}}
<div class="a-bulk">
    <span style="padding-inline: 6px;">{{ trans_choice('admin.widgets.selected', $count, ['count' => $count]) }}</span>
    {{ $slot }}
    @if ($clear)
        <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="{{ $clear }}"
                aria-label="{{ __('admin.widgets.clear_selection') }}" style="margin-inline-start: auto; color: var(--white);">
            <x-icon name="x" size="16" />
        </button>
    @endif
</div>
