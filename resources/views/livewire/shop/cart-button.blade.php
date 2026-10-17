<div>
    <button type="button"
        class="d-btn d-btn--ghost d-btn--icon {{ $count > 0 ? 'd-btn--has-count' : '' }}"
        aria-label="{{ __('ui.cart') }}"
        title="{{ __('ui.cart') }}"
        x-on:click="$wire.dispatch('open-cart-drawer')">
        <x-icon name="shopping-bag" :size="20" />
        @if ($count > 0)
            <span class="d-btn__count">{{ $count }}</span>
        @endif
    </button>
</div>
