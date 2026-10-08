@props([
    'icon' => 'ellipsis',
    'label' => null,
])
{{-- Mirror of admin-kit.jsx AMenu: overflow actions. Put <button>/<a>/<hr>
     items in the default slot. --}}
<div x-data="{ open: false }" @click.outside="open = false" style="position: relative; display: inline-flex;" @click.stop>
    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm"
            @click="open = !open" :aria-expanded="open"
            aria-label="{{ $label ?? __('admin.widgets.more_actions') }}">
        <x-icon :name="$icon" size="16" />
    </button>
    <div class="a-menu" x-show="open" x-cloak @click="open = false">
        {{ $slot }}
    </div>
</div>
