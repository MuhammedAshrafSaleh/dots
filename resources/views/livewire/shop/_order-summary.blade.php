@php
    /** @var \App\Models\Order $order */
@endphp
<aside class="d-summary">
    <h3>{{ __('ui.order_summary') }}</h3>
    <div>
        @foreach ($order->items as $it)
            <div class="d-line">
                <div class="d-line__img">
                    <span style="display:block;width:100%;height:100%;border-radius:6px;background:var(--bg-subtle) center/cover no-repeat;@if ($it->image_url) background-image:url('{{ $it->image_url }}');@endif"></span>
                    <span class="d-summary__qty">{{ $it->qty }}</span>
                </div>
                <div style="min-width:0;">
                    <p class="d-line__name" style="font-size:13px;">{{ $it->product_name }}</p>
                    @if ($it->variant_label)
                        <div class="d-line__var">{{ $it->variant_label }} · {{ __('ui.pdp_qty') }} {{ $it->qty }}</div>
                    @endif
                    <div class="d-line__var" style="font-family:var(--font-mono);">{{ $it->sku }}</div>
                </div>
                <div class="d-line__price" style="font-size:14px;">{{ number_format((float) $it->line_total, 0) }} {{ __('ui.egp') }}</div>
            </div>
        @endforeach
    </div>
    <div class="d-totals">
        <div><span>{{ __('ui.subtotal') }}</span><b>{{ number_format((float) $order->subtotal, 0) }} {{ __('ui.egp') }}</b></div>
        @if ((float) $order->discount_total > 0)
            <div>
                <span style="display:inline-flex;align-items:center;gap:8px;">{{ __('ui.discount') }}
                    @if ($order->coupon_code)<span class="d-badge d-badge--outline">{{ $order->coupon_code }}</span>@endif
                </span>
                <b style="color:var(--success-600);">− {{ number_format((float) $order->discount_total, 0) }} {{ __('ui.egp') }}</b>
            </div>
        @endif
        <div>
            <span>{{ __('ui.delivery') }}</span>
            <b>@if ((float) $order->delivery_fee == 0.0){{ __('ui.delivery_free') }}@else{{ number_format((float) $order->delivery_fee, 0) }} {{ __('ui.egp') }}@endif</b>
        </div>
        <div class="d-totals__grand"><span>{{ __('ui.order_total') }}</span><b>{{ number_format((float) $order->total, 0) }} {{ __('ui.egp') }}</b></div>
        <span style="font:500 12px var(--font-body);color:var(--fg-3);">{{ __('ui.cart_vat_note') }}</span>
    </div>
</aside>
