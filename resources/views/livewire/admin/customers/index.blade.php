@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $locale = app()->getLocale();
    $statusTone = ['active' => 'success', 'disabled' => 'neutral'];
    $hasDetail = Route::has('admin.customers.show');
    $detailUrl = fn ($c) => $hasDetail ? route('admin.customers.show', $c) : null;
    $initials = fn ($name) => Str::of($name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $orderFilter = [
        ['value' => 'y', 'label' => __('admin.customers.has_orders')],
        ['value' => 'n', 'label' => __('admin.customers.no_orders')],
    ];
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.customers.title')" :sub="__('admin.customers.subtitle')">
        <x-slot:actions>
            <x-button variant="outline" icon="download" wire:click="export">{{ __('admin.customers.export') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.customers.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.customers.search')" />
            <x-select size="sm" :aria-label="__('admin.customers.status')" :placeholder="__('admin.customers.any_status')" wire:model.live="status" :options="$statuses" />
            <x-select size="sm" :aria-label="__('admin.customers.governorate')" :placeholder="__('admin.customers.all_governorates')" wire:model.live="gov" :options="$governorates" />
            <x-select size="sm" :aria-label="__('admin.customers.orders')" :placeholder="__('admin.customers.orders_any')" wire:model.live="orders" :options="$orderFilter" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.customers.col_customer') }}</th>
                <th>{{ __('admin.customers.col_mobile') }}</th>
                <th>{{ __('admin.customers.col_email') }}</th>
                <th class="is-num">{{ __('admin.customers.col_orders') }}</th>
                <th class="is-num">{{ __('admin.customers.col_spent') }}</th>
                <th>{{ __('admin.customers.col_joined') }}</th>
                <th>{{ __('admin.customers.col_status') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $customer)
                <tr wire:key="customer-{{ $customer->id }}" @class(['is-click' => $hasDetail]) @if ($hasDetail) onclick="window.Livewire.navigate('{{ $detailUrl($customer) }}')" @endif>
                    <td>
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <span class="a-avatar" @if ($customer->status->value !== 'active') style="background: var(--ink-150); color: var(--fg-2);" @endif>{{ $initials($customer->name) }}</span>
                            <b style="font-weight: 700;">{{ $customer->name }}</b>
                        </span>
                    </td>
                    <td><span dir="ltr" style="text-align: start;">{{ $customer->mobile }}</span></td>
                    <td><span dir="ltr" style="text-align: start; color: var(--fg-2);">{{ $customer->email }}</span></td>
                    <td class="is-num">{{ (int) $customer->orders_count }}</td>
                    <td class="is-num"><b>{{ number_format((float) $customer->orders_sum_total, 2) }}</b></td>
                    <td><span class="a-mono">{{ $customer->created_at?->translatedFormat('j M Y') }}</span></td>
                    <td><x-badge :tone="$statusTone[$customer->status->value] ?? 'neutral'" dot>{{ __('admin.customers.status_'.$customer->status->value) }}</x-badge></td>
                    <td class="is-shrink" onclick="event.stopPropagation()" style="display: flex; align-items: center; gap: 4px;">
                        <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="color: var(--wa-600);" target="_blank" rel="noopener"
                           href="https://wa.me/{{ \App\Services\WhatsApp::normalizeNumber($customer->mobile) }}"
                           aria-label="{{ __('admin.customers.whatsapp_customer') }}"><x-icon name="message-circle" size="16" /></a>
                        @can('customers.delete')
                            <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="color: var(--danger-600);"
                                wire:click="delete({{ $customer->id }})"
                                wire:confirm="{{ __('admin.customers.delete_confirm') }}"
                                aria-label="{{ __('admin.customers.delete') }}"><x-icon name="trash-2" size="16" /></button>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="users-round" :title="__('admin.customers.empty_title')">
                    <x-slot:actions><x-button variant="outline" size="sm" wire:click="clearFilters">{{ __('admin.customers.clear_filters') }}</x-button></x-slot:actions>
                </x-admin.empty>
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>
