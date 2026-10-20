@php
    use Illuminate\Support\Facades\Route;
    $statusTone = ['active' => 'success', 'disabled' => 'neutral'];
    $payTone = ['pending' => 'warning', 'awaiting_transfer' => 'info', 'paid' => 'success', 'failed' => 'error', 'refunded' => 'neutral'];
    $orderTone = ['new' => 'info', 'confirmed' => 'accent', 'preparing' => 'warning', 'shipped' => 'dark', 'delivered' => 'success', 'cancelled' => 'error'];
    $isActive = $customer->status->value === 'active';
    $egp = __('admin.customers.egp');
    $hasOrderDetail = Route::has('admin.orders.show');
    $hasProductEdit = Route::has('admin.products.edit');
    $crumbs = [['label' => __('admin.customers.title'), 'url' => Route::has('admin.customers') ? route('admin.customers') : null], ['label' => $customer->name]];
@endphp
<div class="a-page">
    <x-admin.head :crumbs="$crumbs" :title="$customer->name" :sub="__('admin.customers.since').' '.$customer->created_at?->translatedFormat('j M Y')">
        <x-slot:extra>
            <span style="margin-inline-start: 10px; vertical-align: middle;">
                <x-badge :tone="$statusTone[$customer->status->value] ?? 'neutral'" dot>{{ __('admin.customers.status_'.$customer->status->value) }}</x-badge>
            </span>
        </x-slot:extra>
        <x-slot:actions>
            @can('customers.update')
                <x-button :variant="$isActive ? 'outline' : 'primary'" :icon="$isActive ? 'user-x' : 'user-check'" wire:click="toggleStatus"
                          :style="$isActive ? 'color: var(--error-600);' : null">
                    {{ $isActive ? __('admin.customers.disable') : __('admin.customers.enable') }}
                </x-button>
            @endcan
            <x-button variant="whatsapp" icon="message-circle" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">{{ __('admin.customers.whatsapp') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    @unless ($isActive)
        <x-admin.alert tone="warning" :title="__('admin.customers.disabled_title')">{{ __('admin.customers.disabled_body') }}</x-admin.alert>
    @endunless

    <div class="a-split">
        <div style="display: grid; gap: 16px; min-width: 0;">
            <div class="a-kpis">
                @php $kpis = [
                    [__('admin.customers.col_orders'), $stats['orders']],
                    [__('admin.customers.total_spent'), number_format($stats['spent'], 2).' '.$egp],
                    [__('admin.customers.average_order'), $stats['orders'] > 0 ? number_format($stats['average'], 2).' '.$egp : '—'],
                    [__('admin.customers.wishlist'), $stats['wishlist']],
                ]; @endphp
                @foreach ($kpis as [$label, $value])
                    <div class="a-card a-kpi" wire:key="kpi-{{ $loop->index }}">
                        <div class="a-kpi__l">{{ $label }}</div>
                        <div class="a-kpi__v" style="font-size: 24px;">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Orders --}}
            <x-admin.card :title="__('admin.customers.col_orders')" :body="false">
                <x-admin.table :empty="$orders->isEmpty()">
                    <x-slot:head>
                        <th>{{ __('admin.customers.col_order') }}</th>
                        <th class="is-num">{{ __('admin.customers.col_items') }}</th>
                        <th class="is-num">{{ __('admin.customers.col_total') }}</th>
                        <th>{{ __('admin.customers.col_payment') }}</th>
                        <th>{{ __('admin.customers.col_status') }}</th>
                    </x-slot:head>
                    @foreach ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" @class(['is-click' => $hasOrderDetail])
                            @if ($hasOrderDetail) onclick="window.Livewire.navigate('{{ route('admin.orders.show', $order) }}')" @endif>
                            <td>
                                <span>
                                    <span class="a-mono" style="color: var(--fg-1); font-weight: 500;">{{ $order->number }}</span>
                                    <span class="a-sub">{{ $order->placed_at?->translatedFormat('j M Y') }}</span>
                                </span>
                            </td>
                            <td class="is-num">{{ (int) $order->items_count }}</td>
                            <td class="is-num"><b>{{ number_format((float) $order->total, 2) }}</b></td>
                            <td><x-badge :tone="$payTone[$order->payment_status->value] ?? 'neutral'" dot>{{ __('admin.orders.pay_'.$order->payment_status->value) }}</x-badge></td>
                            <td><x-badge :tone="$orderTone[$order->status->value] ?? 'neutral'" dot>{{ __('admin.orders.status_'.$order->status->value) }}</x-badge></td>
                        </tr>
                    @endforeach
                    <x-slot:emptyState>
                        <x-admin.empty icon="shopping-bag" :title="__('admin.customers.no_orders_yet')" />
                    </x-slot:emptyState>
                </x-admin.table>
            </x-admin.card>

            {{-- Wishlist --}}
            <x-admin.card :title="__('admin.customers.wishlist')" :body="false">
                @forelse ($customer->wishlist as $product)
                    @php $url = $hasProductEdit ? route('admin.products.edit', $product->id) : null; @endphp
                    <a wire:key="wish-{{ $product->id }}" @if ($url) href="{{ $url }}" wire:navigate @endif
                       style="display: grid; grid-template-columns: 44px minmax(0,1fr) auto; gap: 12px; align-items: center; padding: 10px 18px; border-bottom: 1px solid var(--border-1); text-decoration: none; color: inherit; {{ $url ? 'cursor: pointer;' : '' }}">
                        <x-image-frame :src="$product->getFirstMediaUrl('gallery')" style="width: 44px; height: 44px; border-radius: 6px; border: 1px solid var(--border-1);" />
                        <span style="min-width: 0; font: 700 13px/1.3 var(--font-body);">
                            {{ $product->getTranslation('name', $locale, false) }}
                            <span class="a-sub a-mono">{{ $product->sku }}</span>
                        </span>
                        <span style="text-align: end;">
                            <b style="font: 800 13px var(--font-body);">{{ $product->min_price !== null ? number_format((float) $product->min_price, 2) : __('admin.customers.on_request') }}</b>
                        </span>
                    </a>
                @empty
                    <div class="a-card__body"><x-admin.empty icon="heart" :title="__('admin.customers.wishlist_empty')" /></div>
                @endforelse
            </x-admin.card>
        </div>

        <div style="display: grid; gap: 16px;">
            {{-- Profile --}}
            <x-admin.card :title="__('admin.customers.profile')" icon="user">
                <div style="display: grid; gap: 8px; font: 600 14px/1.45 var(--font-body);">
                    <span style="display: flex; gap: 8px; align-items: center;"><x-icon name="phone" size="16" style="color: var(--fg-3);" /><span dir="ltr">{{ $customer->mobile }}</span></span>
                    <span style="display: flex; gap: 8px; align-items: center;"><x-icon name="mail" size="16" style="color: var(--fg-3);" /><span dir="ltr">{{ $customer->email }}</span></span>
                    @if ($customer->last_login_at)
                        <span style="display: flex; gap: 8px; align-items: center; color: var(--fg-2);"><x-icon name="log-in" size="16" style="color: var(--fg-3);" />{{ __('admin.customers.last_login') }} {{ $customer->last_login_at->translatedFormat('j M Y · H:i') }}</span>
                    @endif
                </div>
            </x-admin.card>

            {{-- Addresses --}}
            <x-admin.card :title="__('admin.customers.addresses')" icon="map-pin">
                @forelse ($customer->addresses as $address)
                    <div wire:key="addr-{{ $address->id }}" class="d-file" style="align-items: flex-start;">
                        <span style="flex: 1; min-width: 0; display: grid; gap: 2px; font: 600 13px/1.45 var(--font-body);">
                            <b style="display: flex; gap: 6px; align-items: center;">
                                {{ $address->label->label() }}
                                @if ($address->is_default)<x-badge tone="dark">{{ __('admin.customers.default') }}</x-badge>@endif
                            </b>
                            <span style="color: var(--fg-2);">{{ collect([$address->street, $address->floor_apartment, $address->city, $address->landmark])->filter()->implode('، ') }}</span>
                            <span style="color: var(--fg-2);">{{ $address->governorate?->getTranslation('name', $locale, false) }}</span>
                        </span>
                    </div>
                @empty
                    <span class="d-help">{{ __('admin.customers.no_addresses') }}</span>
                @endforelse
            </x-admin.card>

            {{-- Internal notes --}}
            <x-admin.card :title="__('admin.customers.internal_notes')" icon="sticky-note">
                @can('customers.update')
                    <div style="display: grid; gap: 10px;">
                        <x-input multiline rows="3" wire:model="adminNotes" :placeholder="__('admin.customers.notes_ph')" :aria-label="__('admin.customers.internal_notes')" :error="$errors->first('adminNotes')" />
                        <div><x-button size="sm" variant="dark" icon="check" wire:click="saveNotes">{{ __('admin.customers.save_notes') }}</x-button></div>
                    </div>
                @else
                    <span class="d-help">{{ $customer->admin_notes ?: __('admin.customers.no_notes') }}</span>
                @endcan
            </x-admin.card>
        </div>
    </div>
</div>
