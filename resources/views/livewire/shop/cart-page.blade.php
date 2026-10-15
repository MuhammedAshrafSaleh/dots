@php
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\CartItem> $items
     * @var \App\Support\CartTotals $totals
     * @var \Illuminate\Support\Collection<int, \App\Models\Product> $suggestions
     * @var string $locale
     */
@endphp

<div class="d-container" style="padding-block:32px 64px;">
    <h1 class="t-h2" style="margin:0 0 24px;">{{ __('ui.cart') }}</h1>

    @if ($items->isEmpty())
        <div style="text-align:center;padding:64px 0;">
            <p style="color:var(--fg-3);margin-bottom:16px;">{{ __('ui.cart_empty') }}</p>
            <a href="{{ route('products', $locale) }}" class="d-btn d-btn--primary">{{ __('ui.continue_shopping') }}</a>
        </div>
    @else
        <div class="d-cart-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:24px;align-items:start;">

            {{-- Left: items --}}
            <div style="display:grid;gap:16px;">
                <div style="border:1px solid var(--border-1);border-radius:var(--radius-lg);padding:4px 20px;">
                    @foreach ($items as $item)
                        @include('livewire.shop._cart-line', ['item' => $item, 'locale' => $locale])
                    @endforeach
                </div>

                {{-- WhatsApp promo --}}
                <div style="display:flex;align-items:center;gap:14px;background:var(--wa-100);border-radius:var(--radius-lg);padding:16px 18px;">
                    <x-icon name="whatsapp" :size="24" style="color:var(--wa-600);flex:none;" />
                    <span style="flex:1;font:600 14px/1.4 var(--font-body);">{{ __('ui.cart_wa_promo') }}</span>
                    <x-whatsapp-button source="cart" :label="__('ui.cart_quote')" />
                </div>

                <a href="{{ route('products', $locale) }}" class="d-btn d-btn--ghost" style="justify-self:start;">
                    <x-icon name="arrow-left" :size="16" />{{ __('ui.continue_shopping') }}
                </a>
            </div>

            {{-- Right: order summary --}}
            <aside class="d-summary">
                <h3>{{ __('ui.order_summary') }}</h3>

                {{-- Promo code --}}
                <div>
                    <div class="d-promo">
                        <input type="text" class="d-input" wire:model="couponCode"
                            placeholder="{{ __('ui.promo_code') }}"
                            wire:keydown.enter.prevent="applyCoupon">
                        <button type="button" class="d-btn d-btn--dark" wire:click="applyCoupon" wire:loading.attr="disabled">
                            {{ __('ui.apply') }}
                        </button>
                    </div>
                    @if ($couponError)
                        <span style="display:block;margin-top:6px;font:600 12px/1.4 var(--font-body);color:var(--error-600);">
                            {{ __($couponError, $couponErrorParams) }}
                        </span>
                    @elseif ($totals->couponCode)
                        <span style="display:block;margin-top:6px;font:600 12px/1.4 var(--font-body);color:var(--success-600);">
                            {{ __('ui.coupon_applied', ['code' => $totals->couponCode]) }}
                            <button type="button" wire:click="removeCoupon"
                                style="background:none;border:0;padding:0;color:var(--fg-3);text-decoration:underline;cursor:pointer;font:inherit;">
                                {{ __('ui.remove') }}
                            </button>
                        </span>
                    @endif
                </div>

                {{-- Totals --}}
                <div class="d-totals">
                    <div><span>{{ __('ui.subtotal') }}</span><b>{{ number_format($totals->subtotal, 0) }} {{ __('ui.egp') }}</b></div>
                    <div>
                        <span>{{ __('ui.delivery_estimate') }}</span>
                        <b>
                            @if ($totals->deliveryFree || $totals->deliveryFee == 0.0)
                                {{ __('ui.delivery_free') }}
                            @else
                                {{ number_format($totals->deliveryFee, 0) }} {{ __('ui.egp') }}
                            @endif
                        </b>
                    </div>
                    @if ($totals->discount > 0)
                        <div style="color:var(--success-600);">
                            <span>{{ __('ui.discount') }}</span>
                            <b style="color:var(--success-600);">− {{ number_format($totals->discount, 0) }} {{ __('ui.egp') }}</b>
                        </div>
                    @endif
                    <div class="d-totals__grand">
                        <span>{{ __('ui.order_total') }}</span>
                        <b>{{ number_format($totals->total, 0) }} {{ __('ui.egp') }}</b>
                    </div>
                </div>
                <span style="font:600 12px/1.4 var(--font-body);color:var(--fg-3);">{{ __('ui.cart_vat_note') }}</span>

                <a href="{{ route('checkout', $locale) }}" class="d-btn d-btn--primary d-btn--lg d-btn--block">
                    {{ __('ui.checkout') }}<x-icon name="arrow-right" :size="18" />
                </a>
            </aside>
        </div>

        {{-- Suggestions --}}
        @if ($suggestions->isNotEmpty())
            <section style="margin-top:56px;">
                <h2 class="t-h3" style="margin:0 0 20px;">{{ __('ui.cart_suggestions') }}</h2>
                <div class="d-pgrid">
                    @foreach ($suggestions as $product)
                        @include('site._product-card', ['product' => $product, 'locale' => $locale])
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</div>
