<x-layouts.app :title="__('ui.confirm_title')">
    <x-seo :title="__('ui.confirm_title')" :noindex="true" />

    <div class="d-container" style="padding-block:48px 72px;max-width:720px;">
        <div style="text-align:center;margin-bottom:32px;">
            <span style="display:inline-grid;place-items:center;width:64px;height:64px;border-radius:50%;background:var(--success-100);color:var(--success-600);margin-bottom:16px;">
                <x-icon name="check" :size="32" :stroke="2.5" />
            </span>
            <h1 class="t-h2" style="margin:0 0 8px;">{{ __('ui.confirm_heading') }}</h1>
            <p style="color:var(--fg-2);font:500 15px/1.5 var(--font-body);">{{ __('ui.confirm_keep_number') }}</p>
        </div>

        <div style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:24px;display:grid;gap:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span style="font:600 14px var(--font-body);color:var(--fg-3);">{{ __('ui.order_number') }}</span>
                <b style="font:800 20px var(--font-mono);letter-spacing:.5px;">{{ $order->number }}</b>
            </div>

            <div class="d-totals">
                <div><span>{{ __('ui.subtotal') }}</span><b>{{ number_format((float) $order->subtotal, 0) }} {{ __('ui.egp') }}</b></div>
                <div>
                    <span>{{ __('ui.delivery') }}</span>
                    <b>@if ((float) $order->delivery_fee == 0.0){{ __('ui.delivery_free') }}@else{{ number_format((float) $order->delivery_fee, 0) }} {{ __('ui.egp') }}@endif</b>
                </div>
                @if ((float) $order->discount_total > 0)
                    <div style="color:var(--success-600);"><span>{{ __('ui.discount') }}</span><b style="color:var(--success-600);">− {{ number_format((float) $order->discount_total, 0) }} {{ __('ui.egp') }}</b></div>
                @endif
                <div class="d-totals__grand"><span>{{ __('ui.order_total') }}</span><b>{{ number_format((float) $order->total, 0) }} {{ __('ui.egp') }}</b></div>
            </div>

            @if ($order->payment_status === \App\Enums\OrderPaymentStatus::AwaitingTransfer)
                <p style="font:600 14px/1.5 var(--font-body);color:var(--fg-2);background:var(--wa-100);border-radius:var(--radius-md);padding:14px 16px;margin:0;">
                    {{ __('ui.confirm_instapay_note') }}
                </p>
            @endif
        </div>

        <div style="display:flex;gap:12px;justify-content:center;margin-top:28px;flex-wrap:wrap;">
            <a href="{{ route('track', $locale) }}" class="d-btn d-btn--dark">{{ __('ui.track_order') }}</a>
            <a href="{{ route('products', $locale) }}" class="d-btn d-btn--ghost">{{ __('ui.continue_shopping') }}</a>
        </div>
    </div>
</x-layouts.app>
