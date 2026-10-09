@props([
    'title' => null,
    'sub' => null,
    'icon' => null,
    'count' => null,
    'open' => false,
])
{{-- Mirror of admin-kit.jsx ACollapse: collapsible section card. --}}
<section class="a-card" x-data="{ open: @js($open) }">
    <button type="button" class="a-card__head" @click="open = !open" :aria-expanded="open"
            style="width: 100%; border: 0; background: none; cursor: pointer; text-align: start;"
            :style="open ? '' : 'border-bottom: 0'">
        <h3>
            @if ($icon)<x-icon :name="$icon" size="18" />@endif{{ $title }}
            @if ($count !== null)<x-badge tone="neutral">{{ $count }}</x-badge>@endif
        </h3>
        @if ($sub)<span class="a-count" style="flex: 1;">{{ $sub }}</span>@endif
        <span style="margin-inline-start: auto; display: inline-flex;" :style="open ? 'transform: rotate(180deg)' : ''">
            <x-icon name="chevron-down" size="18" />
        </span>
    </button>
    <div class="a-card__body" x-show="open" x-cloak>
        {{ $slot }}
    </div>
</section>
