@php
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\CartItem> $items
     * @var \App\Support\CartTotals $totals
     */
    $locale = app()->getLocale();
    $progress = $totals->freeShipThreshold && $totals->freeShipThreshold > 0
        ? min(100, (int) round($totals->subtotal / $totals->freeShipThreshold * 100))
        : 100;
@endphp

<div>
    {{-- Overlay --}}
    <div class="d-overlay" x-show="$wire.open" x-cloak x-transition.opacity
        wire:click="close" aria-hidden="true"></div>

    {{-- Panel --}}
    <aside class="d-drawer" x-show="$wire.open" x-cloak
        role="dialog" aria-modal="true" aria-label="{{ __('ui.cart') }}"
        x-on:keydown.escape.window="$wire.close()">

        <div class="d-drawer__head">
            <span style="display:inline-flex;align-items:center;gap:8px;font:700 16px/1 var(--font-body);">
                <x-icon name="shopping-bag" :size="18" />
                {{ __('ui.cart_title_n', ['n' => $totals->itemCount]) }}
            </span>
            <button type="button" class="d-btn d-btn--ghost d-btn--icon"
                wire:click="close" aria-label="{{ __('ui.close') }}">
                <x-icon name="x" :size="18" />
            </button>
        </div>

        @if ($items->isEmpty())
            <div class="d-drawer__body">
                <p style="text-align:center;color:var(--fg-3);padding:48px 16px;">{{ __('ui.cart_empty') }}</p>
            </div>
        @else
            @if ($totals->freeShipThreshold)
                <div class="d-ship">
                    <span>
                        @if ($totals->deliveryFree)
                            {{ __('ui.free_ship_unlocked') }}
                        @else
                            {{ __('ui.free_ship_remaining', ['amount' => number_format($totals->freeShipRemaining(), 0)]) }}
                        @endif
                    </span>
                    <div class="d-ship__bar">
                        <div class="d-ship__fill" style="width:{{ $progress }}%"></div>
                    </div>
                </div>
            @endif

            <div class="d-drawer__body">
                @foreach ($items as $item)
                    @include('livewire.shop._cart-line', ['item' => $item, 'locale' => $locale])
                @endforeach
            </div>

            <div class="d-drawer__foot">
                <div class="d-totals">
                    <div>
                        <span>{{ __('ui.subtotal') }}</span>
                        <b>{{ number_format($totals->subtotal, 0) }} {{ __('ui.egp') }}</b>
                    </div>
                </div>
                <span style="font:600 12px/1.4 var(--font-body);color:var(--fg-3);">{{ __('ui.cart_vat_note') }}</span>

                <a href="{{ route('checkout', $locale) }}" class="d-btn d-btn--primary d-btn--lg d-btn--block">
                    {{ __('ui.checkout') }}<x-icon name="arrow-right" :size="18" />
                </a>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <a href="{{ route('cart', $locale) }}" class="d-btn d-btn--outline">{{ __('ui.view_cart') }}</a>
                    <x-whatsapp-button source="cart" :label="__('ui.cart_quote')" />
                </div>
            </div>
        @endif
    </aside>
</div>
