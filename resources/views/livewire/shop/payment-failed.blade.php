@php
    /** @var \App\Models\Order $order */
    /** @var string $locale */

    $options = [
        'retry' => ['icon' => 'credit-card', 'title' => __('ui.pf_retry_t'), 'desc' => __('ui.pf_retry_d'), 'meta' => null],
        'cod' => ['icon' => 'banknote', 'title' => __('ui.cash_on_delivery'), 'desc' => __('ui.pf_cod_d'), 'meta' => number_format((float) $order->total, 0).' '.__('ui.egp')],
        'instapay' => ['icon' => 'smartphone', 'title' => __('ui.instapay_via_whatsapp'), 'desc' => __('ui.pf_instapay_d'), 'meta' => null],
    ];
    $cta = ['retry' => __('ui.pf_retry_cta'), 'cod' => __('ui.pf_cod_cta'), 'instapay' => __('ui.pf_instapay_cta')][$option];
@endphp

<div style="background:var(--bg-subtle);">
    <div class="d-container d-pay-grid" style="padding-block:40px;display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:32px;align-items:start;">
        <div style="display:grid;gap:20px;">

            <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:32px;display:grid;gap:18px;">
                <span style="width:56px;height:56px;border-radius:50%;background:var(--error-100);color:var(--error-600);display:grid;place-items:center;">
                    <x-icon name="circle-alert" :size="28" :stroke="2" />
                </span>
                <div style="display:grid;gap:10px;">
                    <x-eyebrow>{{ $expired ? __('ui.pf_expired_eyebrow') : __('ui.pf_eyebrow') }}</x-eyebrow>
                    <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ $expired ? __('ui.pf_expired_heading') : __('ui.pf_heading') }}</h1>
                    <p style="margin:0;font:500 15px/1.6 var(--font-body);color:var(--fg-2);text-wrap:pretty;">
                        {{ $expired ? __('ui.pf_expired_body', ['number' => $order->number]) : __('ui.pf_body', ['number' => $order->number]) }}
                    </p>
                </div>
            </section>

            @if ($expired)
                <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:24px;display:grid;gap:16px;">
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <button type="button" wire:click="reAddToCart" wire:loading.attr="disabled" class="d-btn d-btn--lg d-btn--primary">
                            <x-icon name="shopping-cart" :size="18" />{{ __('ui.pf_expired_readd_cta') }}
                        </button>
                        <x-whatsapp-button source="order_help" class="d-btn--lg d-btn--ghost" :label="__('ui.pf_get_help')" />
                    </div>
                </section>
            @else
                <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:24px;display:grid;gap:16px;">
                    <h2 style="margin:0;font:600 20px/1.25 var(--font-display);letter-spacing:var(--tracking-heading);">{{ __('ui.pf_how') }}</h2>
                    <div style="display:grid;gap:10px;">
                        @foreach ($options as $value => $opt)
                            <label class="d-radio-card @if ($option === $value) d-radio-card--on @endif">
                                <input type="radio" wire:model.live="option" value="{{ $value }}" style="position:absolute;opacity:0;pointer-events:none;">
                                <span class="d-check"><input type="radio" tabindex="-1" aria-hidden="true" @checked($option === $value)></span>
                                <span style="display:inline-flex;align-items:center;gap:10px;flex:1;min-width:0;">
                                    <x-icon :name="$opt['icon']" :size="20" style="color:var(--fg-3);flex:none;" />
                                    <span style="display:grid;gap:2px;min-width:0;">
                                        <b style="font:800 15px/1.3 var(--font-body);">{{ $opt['title'] }}</b>
                                        <span style="font:500 13px/1.4 var(--font-body);color:var(--fg-2);">{{ $opt['desc'] }}</span>
                                    </span>
                                </span>
                                @if ($opt['meta'])<b style="font:800 14px var(--font-body);white-space:nowrap;">{{ $opt['meta'] }}</b>@endif
                            </label>
                        @endforeach
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <button type="button" wire:click="proceed" wire:loading.attr="disabled"
                            class="d-btn d-btn--lg {{ $option === 'instapay' ? 'd-btn--whatsapp' : 'd-btn--primary' }}">
                            @if ($option === 'retry')<x-icon name="rotate-ccw" :size="18" />@endif{{ $cta }}
                        </button>
                        <x-whatsapp-button source="order_help" class="d-btn--lg d-btn--ghost" :label="__('ui.pf_get_help')" />
                    </div>
                </section>
            @endif
        </div>

        @include('livewire.shop._order-summary', ['order' => $order])
    </div>
</div>
