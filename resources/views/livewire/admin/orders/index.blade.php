@php
    use Illuminate\Support\Facades\Route;
    $locale = app()->getLocale();
    $payTone = ['pending' => 'warning', 'awaiting_transfer' => 'info', 'paid' => 'success', 'failed' => 'error', 'refunded' => 'neutral'];
    $statusTone = ['new' => 'info', 'confirmed' => 'accent', 'preparing' => 'warning', 'shipped' => 'dark', 'delivered' => 'success', 'cancelled' => 'error'];
    $methodIcon = ['cod' => 'banknote', 'card' => 'credit-card', 'instapay_whatsapp' => 'smartphone'];
    $hasDetail = Route::has('admin.orders.show');
    $detailUrl = fn ($o) => $hasDetail ? route('admin.orders.show', $o) : null;
    $statusTabs = ['new', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled'];
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.orders.title')" :sub="__('admin.orders.subtitle')">
        <x-slot:actions>
            <x-button variant="outline" icon="download" wire:click="export">{{ __('admin.orders.export') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div style="padding: 0 18px;">
            <x-tabs
                :value="$tab"
                :tabs="array_merge(
                    [['id' => 'all', 'label' => __('admin.orders.tab_all'), 'count' => $counts['all']]],
                    array_map(fn ($s) => ['id' => $s, 'label' => __('admin.orders.status_'.$s), 'count' => $counts[$s]], $statusTabs),
                )"
                x-on:tab-changed="$wire.set('tab', $event.detail)"
            />
        </div>

        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.orders.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.orders.search')" />
            <x-select size="sm" :aria-label="__('admin.orders.payment_status')" :placeholder="__('admin.orders.any')" wire:model.live="payment" :options="$paymentStatuses" />
            <x-select size="sm" :aria-label="__('admin.orders.payment_method')" :placeholder="__('admin.orders.any')" wire:model.live="method" :options="$methods" />
            <x-input size="sm" type="date" :aria-label="__('admin.orders.from')" wire:model.live="from" dir="ltr" />
            <x-input size="sm" type="date" :aria-label="__('admin.orders.to')" wire:model.live="to" dir="ltr" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.orders.col_order') }}</th>
                <th>{{ __('admin.orders.col_customer') }}</th>
                <th>{{ __('admin.orders.col_governorate') }}</th>
                <th class="is-num">{{ __('admin.orders.col_items') }}</th>
                <th class="is-num">{{ __('admin.orders.col_total') }}</th>
                <th>{{ __('admin.orders.col_method') }}</th>
                <th>{{ __('admin.orders.col_payment') }}</th>
                <th>{{ __('admin.orders.col_status') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $order)
                <tr wire:key="order-{{ $order->id }}" @class(['is-click' => $hasDetail]) @if ($hasDetail) onclick="window.Livewire.navigate('{{ $detailUrl($order) }}')" @endif>
                    <td>
                        <span>
                            <span class="a-mono" style="color: var(--fg-1); font-weight: 500;">{{ $order->number }}</span>
                            <span class="a-sub">{{ $order->placed_at?->translatedFormat('j M Y') }}</span>
                        </span>
                    </td>
                    <td>
                        <span>
                            <b style="font-weight: 700;">{{ $order->contact_name }}</b>
                            <span class="a-sub" dir="ltr" style="text-align: start;">{{ $order->contact_mobile }}</span>
                        </span>
                    </td>
                    <td>{{ $order->address?->governorate?->getTranslation('name', $locale, false) ?? '—' }}</td>
                    <td class="is-num">{{ (int) $order->items_count }}</td>
                    <td class="is-num"><b>{{ number_format((float) $order->total, 2) }}</b></td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; white-space: nowrap;">
                            <x-icon :name="$methodIcon[$order->payment_method_code] ?? 'wallet'" size="16" style="color: var(--fg-3);" />{{ __('admin.orders.method_'.$order->payment_method_code) }}
                        </span>
                    </td>
                    <td><x-badge :tone="$payTone[$order->payment_status->value] ?? 'neutral'" dot>{{ __('admin.orders.pay_'.$order->payment_status->value) }}</x-badge></td>
                    <td><x-badge :tone="$statusTone[$order->status->value] ?? 'neutral'" dot>{{ __('admin.orders.status_'.$order->status->value) }}</x-badge></td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="color: var(--wa-600);" target="_blank" rel="noopener"
                           href="https://wa.me/{{ \App\Services\WhatsApp::normalizeNumber($order->contact_mobile) }}"
                           aria-label="{{ __('admin.orders.whatsapp_customer') }}"><x-icon name="message-circle" size="16" /></a>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="inbox" :title="__('admin.orders.empty_title')">
                    <x-slot:actions><x-button variant="outline" size="sm" wire:click="clearFilters">{{ __('admin.orders.clear_filters') }}</x-button></x-slot:actions>
                </x-admin.empty>
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>
