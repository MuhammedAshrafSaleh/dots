@php
    use Illuminate\Support\Facades\Route;
    $locale = app()->getLocale();
    $payTone = ['pending' => 'warning', 'awaiting_transfer' => 'info', 'paid' => 'success', 'failed' => 'error', 'refunded' => 'neutral'];
    $statusTone = ['new' => 'info', 'confirmed' => 'accent', 'preparing' => 'warning', 'shipped' => 'dark', 'delivered' => 'success', 'cancelled' => 'error'];
    $methodIcon = ['cod' => 'banknote', 'card' => 'credit-card', 'instapay_whatsapp' => 'smartphone'];
    $logTone = ['paid' => 'success', 'failed' => 'error', 'pending' => 'info', 'refunded' => 'neutral'];
    $isCard = $order->payment_method_code === 'card';
    $hasProductEdit = Route::has('admin.products.edit');
@endphp
<div class="a-page">
    @php $crumbs = [['label' => __('admin.orders.title'), 'url' => Route::has('admin.orders') ? route('admin.orders') : null], ['label' => $order->number]]; @endphp
    <x-admin.head :crumbs="$crumbs" :title="$order->number" :sub="($order->placed_at ? __('admin.orders.placed').' '.$order->placed_at->translatedFormat('j M Y') : '').' · '.__('admin.orders.via_website')">
        <x-slot:extra>
            <span style="display: inline-flex; gap: 6px; margin-inline-start: 10px; vertical-align: middle;">
                <x-badge :tone="$statusTone[$order->status->value] ?? 'neutral'" dot>{{ __('admin.orders.status_'.$order->status->value) }}</x-badge>
                <x-badge :tone="$payTone[$order->payment_status->value] ?? 'neutral'" dot>{{ __('admin.orders.pay_'.$order->payment_status->value) }}</x-badge>
            </span>
        </x-slot:extra>
        <x-slot:actions>
            <x-button variant="whatsapp" icon="message-circle" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">{{ __('admin.orders.contact_whatsapp') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split">
        <div style="display: grid; gap: 16px; min-width: 0;">
            {{-- Items + totals --}}
            <x-admin.card :title="__('admin.orders.items').' · '.$order->items->sum('qty')" :body="false">
                @foreach ($order->items as $item)
                    <div style="display: grid; grid-template-columns: 52px minmax(0,1fr) auto; gap: 14px; align-items: center; padding: 12px 18px; border-bottom: 1px solid var(--border-1);">
                        <x-image-frame :src="$item->image_url" style="width: 52px; height: 52px; border-radius: 8px; border: 1px solid var(--border-1);" />
                        <span style="min-width: 0;">
                            @if ($hasProductEdit && $item->product_id)
                                <a class="a-link" style="text-decoration: none;" href="{{ route('admin.products.edit', $item->product_id) }}" wire:navigate>{{ $item->getTranslation('product_name', $locale, false) }}</a>
                            @else
                                <b>{{ $item->getTranslation('product_name', $locale, false) }}</b>
                            @endif
                            <span class="a-sub">{{ $item->getTranslation('variant_label', $locale, false) }} · <span class="a-mono">{{ $item->sku }}</span></span>
                        </span>
                        <span style="text-align: end; font-variant-numeric: tabular-nums;">
                            <b style="font: 800 14px var(--font-body);">{{ number_format((float) $item->line_total, 2) }}</b>
                            <span class="a-sub">{{ $item->qty }} × {{ number_format((float) $item->unit_price, 2) }}</span>
                        </span>
                    </div>
                @endforeach
                <div class="a-card__body" style="max-width: 380px; margin-inline-start: auto; width: 100%;">
                    <div class="d-totals">
                        <div><span>{{ __('admin.orders.subtotal') }}</span><b>{{ number_format((float) $order->subtotal, 2) }}</b></div>
                        @if ((float) $order->discount_total > 0)
                            <div>
                                <span style="display: inline-flex; gap: 8px; align-items: center;">{{ __('admin.orders.coupon') }} <x-badge tone="outline">{{ $order->coupon_code }}</x-badge></span>
                                <b style="color: var(--success-600);">−{{ number_format((float) $order->discount_total, 2) }}</b>
                            </div>
                        @endif
                        <div><span>{{ __('admin.orders.delivery') }} · {{ $order->getTranslation('delivery_method_name', $locale, false) }}</span><b>{{ (float) $order->delivery_fee > 0 ? number_format((float) $order->delivery_fee, 2) : __('admin.orders.free') }}</b></div>
                        <div class="d-totals__grand"><span>{{ __('admin.orders.total') }}</span><b>{{ number_format((float) $order->total, 2) }}</b></div>
                        <span class="a-count">{{ __('admin.orders.includes_vat', ['rate' => rtrim(rtrim(number_format((float) $order->vat_rate, 2), '0'), '.')]) }} {{ number_format((float) $order->vat_amount, 2) }}</span>
                    </div>
                </div>
            </x-admin.card>

            {{-- Paymob transaction log (card orders only) --}}
            @if ($isCard && $order->payments->isNotEmpty())
                <x-admin.card :title="__('admin.orders.paymob_log')" icon="credit-card" :body="false">
                    <x-admin.table :empty="false">
                        <x-slot:head>
                            <th>{{ __('admin.orders.log_time') }}</th>
                            <th>{{ __('admin.orders.log_ref') }}</th>
                            <th>{{ __('admin.orders.log_txn') }}</th>
                            <th class="is-num">{{ __('admin.orders.log_amount') }}</th>
                            <th>{{ __('admin.orders.log_result') }}</th>
                        </x-slot:head>
                        @foreach ($order->payments as $payment)
                            <tr wire:key="pay-{{ $payment->id }}">
                                <td><span class="a-mono">{{ $payment->created_at?->format('Y-m-d H:i:s') }}</span></td>
                                <td><span class="a-mono">{{ $payment->gateway_ref ?? '—' }}</span></td>
                                <td><span class="a-mono">{{ $payment->transaction_id ?? '—' }}</span></td>
                                <td class="is-num">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>
                                    <x-badge :tone="$logTone[$payment->status->value] ?? 'neutral'" dot>{{ __('admin.orders.pay_'.$payment->status->value) }}</x-badge>
                                    @if (! $payment->hmac_verified)<span class="a-count" title="{{ __('admin.orders.hmac_unverified') }}"> ⚠</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                </x-admin.card>
            @endif

            {{-- Status history timeline --}}
            <x-admin.card :title="__('admin.orders.status_history')" icon="history">
                <ol class="a-tl">
                    @foreach ($order->statusHistories->sortByDesc('created_at') as $h)
                        <li wire:key="hist-{{ $h->id }}">
                            <b>{{ __('admin.orders.status_'.$h->status) }}</b>
                            {{ $h->created_at?->translatedFormat('j M Y · H:i') }} · {{ $h->user?->name ?? __('admin.orders.system') }}
                            @if ($h->getTranslation('note', $locale, false))
                                <span style="display: flex; gap: 6px; align-items: center; margin-top: 4px; color: var(--fg-1);"><x-badge tone="info">{{ __('admin.orders.customer_sees') }}</x-badge>“{{ $h->getTranslation('note', $locale, false) }}”</span>
                            @endif
                            @if ($h->internal_note)
                                <span style="display: flex; gap: 6px; align-items: center; margin-top: 4px; color: var(--fg-1);"><x-badge tone="neutral">{{ __('admin.orders.internal') }}</x-badge>“{{ $h->internal_note }}”</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>
        </div>

        <div style="display: grid; gap: 16px;">
            {{-- Update status --}}
            @can('orders.update')
                <x-admin.card :title="__('admin.orders.update_status')" icon="refresh-cw">
                    <div style="display: grid; gap: 12px;">
                        <x-select :label="__('admin.orders.order_status')" wire:model="status" :options="$orderStatuses" />
                        <x-select :label="__('admin.orders.payment_status')" wire:model="paymentStatus" :options="$paymentStatuses" />
                        <x-input :label="__('admin.orders.expected_delivery')" optional type="date" wire:model="expectedDelivery" dir="ltr" :error="$errors->first('expectedDelivery')" />
                        <div class="d-field">
                            <span class="d-label">{{ __('admin.orders.customer_note_label') }} <span class="d-label__opt"> · {{ __('ui.optional') }}</span></span>
                            <x-input multiline rows="2" dir="ltr" placeholder="EN" wire:model="customerNoteEn" aria-label="Customer note EN" />
                            <x-input multiline rows="2" dir="rtl" placeholder="عربي" wire:model="customerNoteAr" aria-label="Customer note AR" />
                        </div>
                        <x-input :label="__('admin.orders.internal_note_label')" optional multiline rows="2" wire:model="internalNote" :placeholder="__('admin.orders.internal_note_ph')" />
                        <x-button icon="check" style="width: 100%;" wire:click="update">{{ __('admin.orders.update_order') }}</x-button>
                        <x-button variant="quote-outline" icon="message-circle" style="width: 100%;" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">{{ __('admin.orders.send_update_whatsapp') }}</x-button>
                        <span class="d-help">{{ __('admin.orders.whatsapp_help') }}</span>
                    </div>
                </x-admin.card>
            @endcan

            {{-- Customer --}}
            <x-admin.card :title="__('admin.orders.customer')" icon="user">
                <div style="display: grid; gap: 4px; font: 600 14px/1.45 var(--font-body);">
                    <span style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <b style="font-weight: 800;">{{ $order->contact_name }}</b>
                        @if ($order->customer_id === null)<x-badge tone="neutral">{{ __('admin.orders.guest') }}</x-badge>@endif
                    </span>
                    <span dir="ltr" style="text-align: start;">{{ $order->contact_mobile }}</span>
                    @if ($order->contact_email)<span style="color: var(--fg-2);">{{ $order->contact_email }}</span>@endif
                    @if ($order->is_company && $order->company_tax_id)
                        <span style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; font: 600 13px var(--font-body);"><x-icon name="building-2" size="14" />{{ __('admin.orders.tax_id') }} <span class="a-mono">{{ $order->company_tax_id }}</span></span>
                    @endif
                </div>
            </x-admin.card>

            {{-- Delivery --}}
            <x-admin.card :title="__('admin.orders.delivery')" icon="truck">
                <div style="display: grid; gap: 4px; font: 600 14px/1.5 var(--font-body); color: var(--fg-2);">
                    <b style="color: var(--fg-1); font-weight: 800;">{{ $order->getTranslation('delivery_method_name', $locale, false) }} · {{ (float) $order->delivery_fee > 0 ? __('admin.orders.egp').' '.number_format((float) $order->delivery_fee, 2) : __('admin.orders.free') }}</b>
                    @if ($order->address)
                        <span>{{ collect([$order->address->street, $order->address->floor_apartment, $order->address->city, $order->address->landmark])->filter()->implode('، ') }}</span>
                        <span>{{ $order->address->governorate?->getTranslation('name', $locale, false) ?? $order->address->getTranslation('governorate_name', $locale, false) }}</span>
                    @else
                        <span>{{ __('admin.orders.no_address') }}</span>
                    @endif
                </div>
            </x-admin.card>

            {{-- Payment --}}
            <x-admin.card :title="__('admin.orders.payment')" icon="wallet">
                <div style="display: grid; gap: 8px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;"><x-icon :name="$methodIcon[$order->payment_method_code] ?? 'wallet'" size="16" style="color: var(--fg-3);" />{{ __('admin.orders.method_'.$order->payment_method_code) }}</span>
                    @if ($order->coupon_code)
                        <span style="display: flex; gap: 8px; align-items: center; font: 600 13px var(--font-body);">{{ __('admin.orders.coupon_used') }} <x-badge tone="outline">{{ $order->coupon_code }}</x-badge></span>
                    @endif
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
