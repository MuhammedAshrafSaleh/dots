@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Order> $orders */
    /** @var array{orders:int,wishlist:int,addresses:int} $counts */
    /** @var string $locale */
@endphp

<div>
    <x-site.account.shell current="orders" :counts="$counts" :heading="__('account.nav.orders')">
        @forelse ($orders as $order)
            <article class="d-account__box" style="gap:14px;padding:20px;">
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:start;">
                    <div style="display:grid;gap:5px;">
                        <b style="font:500 17px/1 var(--font-mono);" dir="ltr">{{ $order->number }}</b>
                        <span style="font:600 13px var(--font-body);color:var(--fg-3);">
                            {{ __('account.orders.placed') }} {{ $order->placed_at?->translatedFormat('j M Y') }} ·
                            {{ $order->items->sum('qty') }} {{ __('account.orders.items') }}
                        </span>
                    </div>
                    <x-site.order-badges :order="$order" />
                </div>
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding-top:14px;border-top:1px solid var(--border-1);">
                    <x-site.order-thumbs :items="$order->items" />
                    <span style="margin-inline-start:auto;display:grid;gap:3px;text-align:end;">
                        <span style="font:600 12px var(--font-body);color:var(--fg-3);">{{ __('account.orders.total') }}</span>
                        <b style="font:800 16px/1 var(--font-body);">{{ number_format((float) $order->total, 0) }} {{ __('ui.egp') }}</b>
                    </span>
                    <a href="{{ route('account.order', [$locale, $order->number]) }}" wire:navigate class="d-btn d-btn--outline d-btn--sm">
                        {{ __('account.overview.view_order') }}<x-icon name="chevron-right" size="16" />
                    </a>
                </div>
            </article>
        @empty
            <div class="d-account__empty">
                <span class="d-account__empty-ico"><x-icon name="package" size="28" /></span>
                <h2>{{ __('account.orders.empty_title') }}</h2>
                <p>{{ __('account.orders.empty_body') }}</p>
                <div style="margin-top:6px;">
                    <a href="{{ route('products', $locale) }}" class="d-btn d-btn--dark">{{ __('account.overview.browse') }}<x-icon name="arrow-right" size="18" /></a>
                </div>
            </div>
        @endforelse
    </x-site.account.shell>
</div>
