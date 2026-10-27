@php
    use App\Enums\OrderPaymentStatus;
    use App\Enums\PaymentMethodCode;

    /** @var \App\Models\Order $order */
    /** @var string $locale */

    $code = $order->payment_method_code;
    $isInstapay = $code === PaymentMethodCode::InstapayWhatsapp->value;
    $isCard = $code === PaymentMethodCode::Card->value;
    $reserved = $order->payment_status === OrderPaymentStatus::AwaitingTransfer;

    // Payment status badge (tone + label) straight from the order.
    $payTone = match ($order->payment_status) {
        OrderPaymentStatus::Paid => ['success', __('ui.pay_status_paid')],
        OrderPaymentStatus::AwaitingTransfer => ['warning', __('ui.pay_status_awaiting')],
        OrderPaymentStatus::Failed => ['error', __('ui.pay_status_failed')],
        OrderPaymentStatus::Refunded => ['neutral', __('ui.pay_status_refunded')],
        default => $isInstapay ? ['warning', __('ui.pay_status_awaiting')] : ['neutral', __('ui.pay_status_cod')],
    };

    // "What happens next" steps — purely transactional UI copy (SMS/courier free).
    $variant = $isInstapay ? 'instapay' : ($isCard ? 'card' : 'cod');
    $steps = [
        [__('ui.'.$variant.'_step1_t'), __('ui.'.$variant.'_step1_b')],
        [__('ui.'.$variant.'_step2_t'), __('ui.'.$variant.'_step2_b')],
        [__('ui.'.$variant.'_step3_t'), __('ui.'.$variant.'_step3_b')],
    ];
@endphp

<x-layouts.app :title="__('ui.confirm_title')">
    <x-seo :title="__('ui.confirm_title')" :noindex="true" />

    <div style="background:var(--bg-subtle);">
        <div class="d-container" style="padding-block:40px;display:grid;gap:24px;">
            <div class="d-confirm-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:32px;align-items:start;">

                {{-- Left column --}}
                <div style="display:grid;gap:20px;">

                    {{-- Hero --}}
                    <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:32px;display:grid;gap:18px;">
                        <span style="width:56px;height:56px;border-radius:50%;display:grid;place-items:center;background:{{ $reserved ? 'var(--yellow-100)' : 'var(--success-100)' }};color:{{ $reserved ? 'var(--yellow-800)' : 'var(--success-600)' }};">
                            <x-icon :name="$reserved ? 'clock' : 'check'" :size="28" :stroke="2" />
                        </span>
                        <div style="display:grid;gap:10px;">
                            <x-eyebrow>{{ $reserved ? __('ui.confirm_eyebrow_reserved') : __('ui.confirm_eyebrow_placed') }}</x-eyebrow>
                            <h1 class="t-h2" style="margin:0;text-wrap:balance;">
                                {{ $reserved ? __('ui.confirm_heading_reserved') : __('ui.confirm_heading') }}
                            </h1>
                            <p style="margin:0;font:500 15px/1.6 var(--font-body);color:var(--fg-2);">{{ __('ui.confirm_keep_number') }}</p>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:10px 24px;align-items:center;padding-top:16px;border-top:1px solid var(--border-1);">
                            <span style="display:grid;gap:4px;">
                                <span style="font:600 12px var(--font-body);color:var(--fg-3);">{{ __('ui.order_number') }}</span>
                                <b style="font:500 18px/1 var(--font-mono);">{{ $order->number }}</b>
                            </span>
                            <span style="display:grid;gap:4px;">
                                <span style="font:600 12px var(--font-body);color:var(--fg-3);">{{ __('ui.track_placed') }}</span>
                                <b style="font:700 15px/1 var(--font-body);">{{ $order->placed_at?->translatedFormat('j M Y · H:i') }}</b>
                            </span>
                            <span style="display:grid;gap:4px;">
                                <span style="font:600 12px var(--font-body);color:var(--fg-3);">{{ __('ui.checkout_payment') }}</span>
                                <span class="d-badge d-badge--{{ $payTone[0] }}">{{ $payTone[1] }}</span>
                            </span>
                        </div>
                    </section>

                    {{-- Save your details (guests only; hidden once an account exists). --}}
                    <livewire:account.save-order-account :order="$order" :key="'save-order-'.$order->id" />

                    {{-- InstaPay: transfer details box --}}
                    @if ($isInstapay)
                        <section data-instapay-details style="border:1px solid var(--wa-600);border-radius:var(--radius-lg);background:var(--white);padding:24px;display:grid;gap:16px;">
                            <h2 style="margin:0;font:600 20px/1.25 var(--font-display);letter-spacing:var(--tracking-heading);">{{ __('ui.confirm_instapay_details_title') }}</h2>
                            <dl style="margin:0;display:grid;gap:12px;">
                                <div style="display:grid;gap:2px;">
                                    <dt style="font:600 12px var(--font-body);color:var(--fg-3);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);">{{ __('ui.confirm_instapay_address_label') }}</dt>
                                    @if ($instapayAddress !== '')
                                        <dd style="margin:0;font:700 18px/1.3 var(--font-mono);" data-instapay-address>{{ $instapayAddress }}</dd>
                                    @else
                                        <dd style="margin:0;font:500 14px/1.5 var(--font-body);color:var(--fg-2);" data-instapay-address-fallback>{{ __('ui.confirm_instapay_address_fallback') }}</dd>
                                    @endif
                                </div>
                                <div style="display:grid;gap:2px;">
                                    <dt style="font:600 12px var(--font-body);color:var(--fg-3);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);">{{ __('ui.confirm_instapay_amount_label') }}</dt>
                                    <dd style="margin:0;font:700 18px/1.3 var(--font-body);">{{ number_format((float) $order->total, 2) }} {{ __('ui.egp') }}</dd>
                                </div>
                                <div style="display:grid;gap:2px;">
                                    <dt style="font:600 12px var(--font-body);color:var(--fg-3);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);">{{ __('ui.order_number') }}</dt>
                                    <dd style="margin:0;font:500 15px/1.3 var(--font-mono);">{{ $order->number }}</dd>
                                </div>
                                @if ($order->reserved_until !== null)
                                    <div style="display:grid;gap:2px;">
                                        <dt style="font:600 12px var(--font-body);color:var(--fg-3);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);">{{ __('ui.confirm_instapay_deadline_label') }}</dt>
                                        <dd style="margin:0;font:500 15px/1.4 var(--font-body);" data-instapay-deadline>{{ $order->reserved_until->translatedFormat('j M Y · H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </section>
                    @endif

                    {{-- What happens next --}}
                    <section style="border:1px solid {{ $isInstapay ? 'var(--wa-600)' : 'var(--border-1)' }};border-radius:var(--radius-lg);background:var(--white);padding:24px;display:grid;gap:16px;@if ($isInstapay) box-shadow:inset 0 0 0 1px var(--wa-600);@endif">
                        <h2 style="margin:0;font:600 20px/1.25 var(--font-display);letter-spacing:var(--tracking-heading);">{{ __('ui.next_steps_title') }}</h2>
                        <ol style="list-style:none;margin:0;padding:0;display:grid;gap:18px;">
                            @foreach ($steps as $i => $step)
                                <li style="display:grid;grid-template-columns:32px minmax(0,1fr);gap:14px;align-items:start;">
                                    <span class="d-co__num" @if ($i > 0) style="background:var(--white);color:var(--ink-900);box-shadow:inset 0 0 0 1.5px var(--ink-900);" @endif>{{ $i + 1 }}</span>
                                    <div style="display:grid;gap:4px;padding-top:4px;">
                                        <b style="font:800 15px/1.35 var(--font-body);">{{ $step[0] }}</b>
                                        <span style="font:500 14px/1.55 var(--font-body);color:var(--fg-2);text-wrap:pretty;">{{ $step[1] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        @if ($isInstapay)
                            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                                <x-whatsapp-button source="instapay_order" :model="$order" class="d-btn--lg" :label="__('ui.continue_on_whatsapp')" />
                            </div>
                        @endif
                    </section>

                    {{-- Delivery / address / payment details --}}
                    <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:24px;">
                        <div class="d-confirm-cells" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;">
                            <div style="display:grid;gap:6px;align-content:start;">
                                <span style="display:inline-flex;align-items:center;gap:8px;font:800 12px/1 var(--font-body);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);color:var(--fg-3);"><x-icon name="truck" :size="16" />{{ __('ui.delivery') }}</span>
                                <b style="font:800 15px/1.35 var(--font-body);">{{ $order->getTranslation('delivery_method_name', $locale) }}</b>
                                <span style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">
                                    @if ((float) $order->delivery_fee == 0.0){{ __('ui.delivery_free') }}@else{{ number_format((float) $order->delivery_fee, 0) }} {{ __('ui.egp') }}@endif
                                </span>
                                @if ($order->expected_delivery_at !== null)
                                    <span style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">{{ __('ui.expected_delivery') }}: {{ $order->expected_delivery_at->translatedFormat('j M Y') }}</span>
                                @endif
                            </div>

                            @if ($order->address !== null)
                                <div style="display:grid;gap:6px;align-content:start;">
                                    <span style="display:inline-flex;align-items:center;gap:8px;font:800 12px/1 var(--font-body);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);color:var(--fg-3);"><x-icon name="map-pin" :size="16" />{{ __('ui.address_label') }}</span>
                                    <b style="font:800 15px/1.35 var(--font-body);">{{ $order->address->recipient_name }}</b>
                                    <span style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">
                                        {{ $order->address->street }}@if ($order->address->floor_apartment), {{ $order->address->floor_apartment }}@endif,
                                        {{ $order->address->city }}, {{ $order->address->governorate_name }}
                                    </span>
                                    <span dir="ltr" style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">{{ $order->address->mobile }}</span>
                                </div>
                            @endif

                            <div style="display:grid;gap:6px;align-content:start;">
                                <span style="display:inline-flex;align-items:center;gap:8px;font:800 12px/1 var(--font-body);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);color:var(--fg-3);"><x-icon :name="$isInstapay ? 'smartphone' : ($isCard ? 'credit-card' : 'banknote')" :size="16" />{{ __('ui.checkout_payment') }}</span>
                                <b style="font:800 15px/1.35 var(--font-body);">{{ $order->paymentMethod?->name }}</b>
                                <span style="font:500 14px/1.5 var(--font-body);color:var(--fg-2);">{{ $payTone[1] }}</span>
                            </div>
                        </div>
                    </section>

                    {{-- Actions --}}
                    <div style="display:flex;flex-wrap:wrap;gap:10px;">
                        <a href="{{ route('track', $locale) }}?number={{ $order->number }}" class="d-btn d-btn--dark d-btn--lg">
                            <x-icon name="package-search" :size="18" />{{ __('ui.track_order') }}
                        </a>
                        <a href="{{ route('products', $locale) }}" class="d-btn d-btn--outline d-btn--lg">{{ __('ui.continue_shopping') }}</a>
                        <x-whatsapp-button source="order_help" class="d-btn--lg d-btn--quote-outline" :label="__('ui.whatsapp_us')" />
                    </div>
                </div>

                {{-- Right: order summary --}}
                @include('livewire.shop._order-summary', ['order' => $order])
            </div>
        </div>
    </div>
</x-layouts.app>
