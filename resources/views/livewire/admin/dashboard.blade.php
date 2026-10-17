@php
    use Illuminate\Support\Facades\Route;
    $locale = app()->getLocale();
    $payTone = ['pending' => 'warning', 'awaiting_transfer' => 'info', 'paid' => 'success', 'failed' => 'error', 'refunded' => 'neutral'];
    $statusTone = ['new' => 'info', 'confirmed' => 'accent', 'preparing' => 'warning', 'shipped' => 'dark', 'delivered' => 'success', 'cancelled' => 'error'];
    $leadStatusTone = ['new' => 'info', 'contacted' => 'warning', 'converted' => 'success', 'closed' => 'neutral'];
    $leadSourceIcon = [
        'product' => 'package', 'cart' => 'shopping-cart', 'instapay_order' => 'smartphone',
        'boq' => 'file-text', 'brand' => 'award', 'service' => 'wrench', 'project' => 'building-2',
        'article' => 'newspaper', 'order_help' => 'life-buoy', 'general' => 'message-circle',
    ];
    $hasOrders = Route::has('admin.orders');
    $hasLeads = Route::has('admin.leads');
    $hasProducts = Route::has('admin.products');
    $egp = __('admin.orders.egp');
    $kpis = [
        ['shopping-bag', __('admin.dashboard.kpi_orders_today'), number_format($ordersToday), __('admin.dashboard.kpi_orders_today_sub')],
        ['banknote', __('admin.dashboard.kpi_revenue_month'), number_format($revenueMonth, 2).' '.$egp, __('admin.dashboard.kpi_revenue_month_sub')],
        ['message-circle', __('admin.dashboard.kpi_whatsapp_today'), number_format($whatsappToday), __('admin.dashboard.kpi_whatsapp_today_sub')],
        ['calendar-check', __('admin.dashboard.kpi_consultations_new'), number_format($consultationsNew), __('admin.dashboard.kpi_consultations_new_sub')],
    ];
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.dashboard.welcome', ['name' => $name])" :sub="__('admin.dashboard.lede')">
        <x-slot:actions>
            @if ($hasProducts)
                <x-button icon="plus" href="{{ route('admin.products.create') }}" wire:navigate>{{ __('admin.dashboard.add_product') }}</x-button>
            @endif
        </x-slot:actions>
    </x-admin.head>

    <div class="a-kpis">
        @foreach ($kpis as [$icon, $label, $value, $sub])
            <div class="a-card a-kpi" wire:key="kpi-{{ $label }}">
                <div class="a-kpi__l"><span class="a-kpi__ico"><x-icon :name="$icon" size="18" /></span>{{ $label }}</div>
                <div class="a-kpi__v">{{ $value }}</div>
                <div class="a-kpi__d">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    <div class="a-split a-split--wide">
        <x-admin.card :title="__('admin.dashboard.recent_orders')" :body="false">
            @if ($hasOrders)
                <x-slot:action>
                    <x-button variant="ghost" size="sm" icon-end="arrow-right" href="{{ route('admin.orders') }}" wire:navigate>{{ __('admin.dashboard.all_orders') }}</x-button>
                </x-slot:action>
            @endif

            @forelse ($recentOrders as $order)
                <a @class(['a-tree__row']) style="text-decoration: none; color: inherit; cursor: {{ $hasOrders ? 'pointer' : 'default' }};"
                   @if ($hasOrders) href="{{ route('admin.orders.show', $order) }}" wire:navigate @endif
                   wire:key="recent-{{ $order->id }}">
                    <span style="flex: 1 1 160px; min-width: 0;">
                        <span class="a-mono" style="color: var(--fg-1); font-weight: 500;">{{ $order->number }}</span>
                        <span class="a-sub">{{ $order->contact_name }}</span>
                    </span>
                    <b style="font: 800 14px var(--font-body);">{{ number_format((float) $order->total, 2) }} {{ $egp }}</b>
                    <x-badge :tone="$payTone[$order->payment_status->value] ?? 'neutral'" dot>{{ __('admin.orders.pay_'.$order->payment_status->value) }}</x-badge>
                    <x-badge :tone="$statusTone[$order->status->value] ?? 'neutral'" dot>{{ __('admin.orders.status_'.$order->status->value) }}</x-badge>
                </a>
            @empty
                <div style="padding: 24px 18px;"><span class="a-count">{{ __('admin.dashboard.no_orders') }}</span></div>
            @endforelse
        </x-admin.card>

        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.dashboard.low_stock')" :body="false">
                @if ($hasProducts)
                    <x-slot:action>
                        <x-button variant="ghost" size="sm" icon-end="arrow-right" href="{{ route('admin.products') }}" wire:navigate>{{ __('admin.dashboard.products') }}</x-button>
                    </x-slot:action>
                @endif

                @forelse ($lowStock as $product)
                    <a style="display: grid; grid-template-columns: 40px minmax(0,1fr) auto; gap: 12px; align-items: center; padding: 10px 18px; border-bottom: 1px solid var(--border-1); text-decoration: none; color: inherit;"
                       @if ($hasProducts) href="{{ route('admin.products.edit', $product) }}" wire:navigate @endif
                       wire:key="low-{{ $product->id }}">
                        <x-image-frame :src="$product->getFirstMediaUrl('gallery')" style="width: 40px; height: 40px; border-radius: 6px; border: 1px solid var(--border-1);" />
                        <span style="min-width: 0; font: 700 13px/1.3 var(--font-body); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $product->getTranslation('name', $locale, false) }}<span class="a-sub a-mono">{{ $product->sku }}</span>
                        </span>
                        <span style="text-align: end;">
                            <b style="font: 800 15px var(--font-body); color: {{ (int) $product->stock_total === 0 ? 'var(--error-600)' : 'var(--warning-600)' }};">{{ (int) $product->stock_total }}</b>
                            <span class="a-sub">{{ __('admin.dashboard.left') }}</span>
                        </span>
                    </a>
                @empty
                    <div style="padding: 24px 18px;"><span class="a-count">{{ __('admin.dashboard.no_low_stock') }}</span></div>
                @endforelse
            </x-admin.card>

            <x-admin.card :title="__('admin.dashboard.latest_leads')" :body="false">
                @if ($hasLeads)
                    <x-slot:action>
                        <x-button variant="ghost" size="sm" icon-end="arrow-right" href="{{ route('admin.leads') }}" wire:navigate>{{ __('admin.dashboard.all_leads') }}</x-button>
                    </x-slot:action>
                @endif

                @forelse ($latestLeads as $lead)
                    @php
                        $ref = $lead->productVariant?->product?->getTranslation('name', $locale, false) ?? $lead->page_url;
                    @endphp
                    <div style="display: grid; grid-template-columns: 32px minmax(0,1fr) auto; gap: 12px; align-items: center; padding: 10px 18px; border-bottom: 1px solid var(--border-1);" wire:key="dlead-{{ $lead->id }}">
                        <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--wa-100); color: var(--wa-600); display: grid; place-items: center;">
                            <x-icon :name="$leadSourceIcon[$lead->source->value] ?? 'message-circle'" size="16" />
                        </span>
                        <span style="min-width: 0; font: 700 13px/1.3 var(--font-body);">
                            {{ $lead->contact_name ?: __('admin.leads.unknown') }} · <span style="color: var(--fg-3); font-weight: 600;">{{ __('admin.leads.source_'.$lead->source->value) }}</span>
                            <span class="a-sub" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $ref }}</span>
                        </span>
                        <x-badge :tone="$leadStatusTone[$lead->status->value] ?? 'neutral'" dot>{{ __('admin.leads.status_'.$lead->status->value) }}</x-badge>
                    </div>
                @empty
                    <div style="padding: 24px 18px;"><span class="a-count">{{ __('admin.dashboard.no_leads') }}</span></div>
                @endforelse
            </x-admin.card>
        </div>
    </div>

    <x-admin.card :title="__('admin.dashboard.quick')" :body="false">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1px; background: var(--border-1);">
            @foreach (config('admin_nav.groups') as $group)
                @foreach ($group['items'] as [$key, $icon, $labelKey, $routeName, $permission])
                    @continue($key === 'dashboard')
                    @if (Route::has($routeName) && auth('admin')->user()?->can($permission))
                        <a href="{{ route($routeName) }}" wire:navigate
                           style="display: flex; align-items: center; gap: 10px; padding: 16px 18px; background: var(--surface-1); color: var(--fg-1); text-decoration: none; font: 700 14px var(--font-body);">
                            <x-icon :name="$icon" size="18" />{{ __('admin.nav.'.$labelKey) }}
                        </a>
                    @endif
                @endforeach
            @endforeach
        </div>
    </x-admin.card>
</div>
