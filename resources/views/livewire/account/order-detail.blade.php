@php
    use App\Enums\OrderStatus;

    /** @var \App\Models\Order $order */
    /** @var list<array{status:string,label:string,note:?string,time:?string,state:string}> $timeline */
    /** @var int $stepIndex */
    /** @var int $stepCount */
    /** @var string $locale */

    $cancelled = $order->status === OrderStatus::Cancelled;
    $progress = $stepCount > 1 ? (int) round(($stepIndex / ($stepCount - 1)) * 100) : 0;
    $addr = $order->address;
    $labelMeta = $addr?->label ? \App\Enums\AddressLabel::tryFrom($addr->label) : null;
@endphp

<div>
    <x-site.account.shell current="orders" :counts="[]" :crumbs="[
        ['label' => __('account.nav.orders'), 'url' => route('account.orders', $locale)],
        ['label' => $order->number],
    ]">
        @if (session('account.flash'))
            <div class="d-auth__alert d-auth__alert--success" role="status" wire:key="flash">
                <span class="d-auth__alert-icon"><x-icon name="check" size="18" /></span>
                <div style="padding-top:5px;"><b>{{ session('account.flash') }}</b></div>
            </div>
        @endif

        {{-- Header --}}
        <div style="display:grid;gap:14px;">
            <a href="{{ route('account.orders', $locale) }}" wire:navigate style="justify-self:start;display:inline-flex;align-items:center;gap:6px;font:800 13px var(--font-body);color:var(--fg-1);text-decoration:none;">
                <x-icon name="arrow-left" size="16" />{{ __('account.order.all_orders') }}
            </a>
            <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:end;">
                <div style="display:grid;gap:10px;">
                    <h1 class="t-h2" style="margin:0;">{{ __('account.order.title') }} <span dir="ltr" style="font-family:var(--font-mono);font-weight:500;">{{ $order->number }}</span></h1>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <span style="font:600 14px var(--font-body);color:var(--fg-2);">{{ __('account.orders.placed') }} {{ $order->placed_at?->translatedFormat('j M Y · H:i') }}</span>
                        <x-site.order-badges :order="$order" />
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <x-whatsapp-button source="order_help" :model="$order" :label="__('account.order.help')" />
                </div>
            </div>
        </div>

        <div class="d-account__detail">
            {{-- Left column --}}
            <div style="display:grid;gap:20px;">
                {{-- Status + timeline --}}
                <div class="d-account__box">
                    <h2 class="d-account__box-title">{{ __('account.order.status') }}</h2>
                    @unless ($cancelled)
                        <div style="height:6px;border-radius:999px;background:var(--border-1);overflow:hidden;">
                            <div style="height:100%;width:{{ $progress }}%;background:var(--ink-900);border-radius:999px;"></div>
                        </div>
                    @endunless
                    <ol style="list-style:none;margin:0;padding:0;">
                        @foreach ($timeline as $step)
                            @php
                                $last = $loop->last;
                                $dot = match ($step['state']) {
                                    'done' => 'background:var(--ink-900);border-color:var(--ink-900);',
                                    'now' => 'background:var(--accent);border-color:var(--ink-900);',
                                    'cancel' => 'background:var(--error-600);border-color:var(--error-600);',
                                    default => 'background:var(--white);border-color:var(--border-control);',
                                };
                            @endphp
                            <li style="display:grid;grid-template-columns:20px minmax(0,1fr);gap:14px;">
                                <span style="position:relative;display:flex;justify-content:center;">
                                    <i style="width:14px;height:14px;border-radius:50%;border:2px solid;margin-top:3px;z-index:1;{{ $dot }}"></i>
                                    @unless ($last)<i style="position:absolute;top:18px;bottom:-3px;width:2px;background:{{ $step['state'] === 'done' ? 'var(--ink-900)' : 'var(--border-1)' }};"></i>@endunless
                                </span>
                                <div style="display:grid;gap:3px;padding-bottom:{{ $last ? '0' : '22px' }};">
                                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                        <b style="font:800 15px/1.3 var(--font-body);color:{{ $step['state'] === 'todo' ? 'var(--fg-3)' : ($step['state'] === 'cancel' ? 'var(--error-600)' : 'var(--fg-1)') }};">{{ $step['label'] }}</b>
                                        <span style="font:600 13px/1.3 var(--font-body);color:var(--fg-3);font-variant-numeric:tabular-nums;">{{ $step['time'] ?? '—' }}</span>
                                    </div>
                                    @if ($step['state'] !== 'todo' && $step['note'])
                                        <span style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">{{ $step['note'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Items --}}
                <div class="d-account__box" style="gap:0;">
                    <h2 class="d-account__box-title" style="margin-bottom:16px;">{{ __('account.order.items') }} · {{ $order->items->sum('qty') }}</h2>
                    @foreach ($order->items as $i => $item)
                        <div style="display:grid;grid-template-columns:72px minmax(0,1fr) auto;gap:14px;align-items:center;padding-block:14px;@if ($i) border-top:1px solid var(--border-1);@endif">
                            <span style="width:72px;height:72px;border-radius:8px;border:1px solid var(--border-1);background:var(--white) center/contain no-repeat;@if ($item->image_url) background-image:url('{{ $item->image_url }}');@endif"></span>
                            <div style="display:grid;gap:3px;min-width:0;">
                                @if ($item->product)
                                    <a href="{{ route('product.show', [$locale, $item->product->getTranslation('slug', $locale)]) }}" wire:navigate style="font:700 14px/1.35 var(--font-body);color:var(--fg-1);text-decoration:none;">{{ $item->product_name }}</a>
                                @else
                                    <span style="font:700 14px/1.35 var(--font-body);color:var(--fg-1);">{{ $item->product_name }}</span>
                                @endif
                                <span style="font:500 13px var(--font-body);color:var(--fg-2);">@if ($item->variant_label){{ $item->variant_label }} · @endif{{ __('account.order.qty') }} {{ $item->qty }} × {{ number_format((float) $item->unit_price, 0) }} {{ __('ui.egp') }}</span>
                                <span style="font:500 12px var(--font-mono);color:var(--fg-3);" dir="ltr">{{ $item->sku }}</span>
                            </div>
                            <b style="font:800 14px var(--font-body);white-space:nowrap;">{{ number_format((float) $item->line_total, 0) }} {{ __('ui.egp') }}</b>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right column --}}
            <div style="display:grid;gap:20px;">
                {{-- Payment summary --}}
                <div class="d-account__box">
                    <h2 class="d-account__box-title">{{ __('account.order.payment_summary') }}</h2>
                    <div class="d-totals" style="border-top:0;padding-top:0;">
                        <div><span>{{ __('account.order.subtotal') }}</span><b>{{ number_format((float) $order->subtotal, 0) }} {{ __('ui.egp') }}</b></div>
                        @if ((float) $order->discount_total > 0)
                            <div><span>{{ __('account.order.discount') }}</span><b style="color:var(--success-600);">−{{ number_format((float) $order->discount_total, 0) }} {{ __('ui.egp') }}</b></div>
                        @endif
                        <div><span>{{ __('ui.delivery') }}</span><b>@if ((float) $order->delivery_fee == 0.0){{ __('ui.delivery_free') }}@else{{ number_format((float) $order->delivery_fee, 0) }} {{ __('ui.egp') }}@endif</b></div>
                        <div class="d-totals__grand"><span>{{ __('account.orders.total') }}</span><b>{{ number_format((float) $order->total, 0) }} {{ __('ui.egp') }}</b></div>
                        <span style="font:500 12px var(--font-body);color:var(--fg-3);">{{ __('ui.cart_vat_note') }}</span>
                    </div>
                </div>

                {{-- Delivery address --}}
                @if ($addr)
                    <div class="d-account__box" style="gap:10px;">
                        <h2 class="d-account__box-title">{{ __('account.order.delivery_address') }}</h2>
                        <span style="display:inline-flex;align-items:center;gap:8px;font:800 14px var(--font-body);">
                            @if ($labelMeta)<x-icon :name="$labelMeta->icon()" size="18" />@endif{{ $addr->recipient_name }}
                        </span>
                        <span style="font:500 14px/1.55 var(--font-body);color:var(--fg-2);">
                            {{ $addr->street }}@if ($addr->floor_apartment), {{ $addr->floor_apartment }}@endif, {{ $addr->city }}, {{ $addr->governorate_name }}
                        </span>
                        <span dir="ltr" style="font:500 14px var(--font-body);color:var(--fg-2);text-align:start;">{{ $addr->mobile }}</span>
                    </div>
                @endif

                {{-- Payment method --}}
                <div class="d-account__box">
                    <h2 class="d-account__box-title">{{ __('ui.checkout_payment') }}</h2>
                    <div style="display:flex;gap:12px;align-items:start;">
                        <span style="width:40px;height:40px;border-radius:50%;background:var(--ink-50);display:grid;place-items:center;flex:none;"><x-icon name="credit-card" size="20" /></span>
                        <span style="display:grid;gap:3px;">
                            <b style="font:800 14px/1.35 var(--font-body);">{{ $order->paymentMethod?->name ?? $order->payment_method_code }}</b>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </x-site.account.shell>
</div>
