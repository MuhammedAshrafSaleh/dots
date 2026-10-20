@php
    use App\Support\Orders\StatusTimeline;

    /** @var \App\Models\Customer $customer */
    /** @var ?\App\Models\Order $latestOrder */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Product> $wishlist */
    /** @var array{orders:int,wishlist:int,addresses:int} $counts */
    /** @var string $locale */

    $first = \Illuminate\Support\Str::of($customer->name)->trim()->explode(' ')->first();

    $tiles = [
        [route('account.orders', $locale), 'package', __('account.nav.orders'), trans_choice('account.overview.tile_orders', $counts['orders'], ['count' => $counts['orders']])],
        [route('account.addresses', $locale), 'map-pin', __('account.nav.addresses'), trans_choice('account.overview.tile_addresses', $counts['addresses'], ['count' => $counts['addresses']])],
        [route('account.profile', $locale), 'user', __('account.nav.profile'), __('account.overview.tile_profile')],
    ];
@endphp

<div>
    <x-site.account.shell current="overview" :counts="$counts">
        <div style="display:grid;gap:8px;" class="d-reveal">
            <x-eyebrow>{{ __('account.overview.member_since', ['date' => $customer->created_at?->translatedFormat('F Y')]) }}</x-eyebrow>
            <h1 class="t-h2" style="margin:0;">{{ __('account.overview.welcome', ['name' => $first]) }}</h1>
            <p style="margin:0;font:500 15px/1.6 var(--font-body);color:var(--fg-2);">{{ __('account.overview.lede') }}</p>
        </div>

        <div class="d-account__ov">
            {{-- Latest order --}}
            <div class="d-account__box">
                @if ($latestOrder)
                    @php
                        $step = StatusTimeline::currentStep($latestOrder);
                        $pct = (int) round(($step / (count(StatusTimeline::LADDER) - 1)) * 100);
                    @endphp
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="display:grid;gap:6px;">
                            <x-eyebrow>{{ __('account.overview.latest_order') }}</x-eyebrow>
                            <b style="font:500 20px/1 var(--font-mono);" dir="ltr">{{ $latestOrder->number }}</b>
                            <span style="font:600 13px var(--font-body);color:var(--fg-3);">
                                {{ $latestOrder->placed_at?->translatedFormat('j M Y') }} ·
                                {{ $latestOrder->items->sum('qty') }} {{ __('account.orders.items') }} ·
                                {{ number_format((float) $latestOrder->total, 0) }} {{ __('ui.egp') }}
                            </span>
                        </div>
                        <x-site.order-badges :order="$latestOrder" style="align-self:start;" />
                    </div>
                    <x-site.order-thumbs :items="$latestOrder->items" />
                    @if ($latestOrder->status !== \App\Enums\OrderStatus::Cancelled)
                        <div style="height:6px;border-radius:999px;background:var(--border-1);overflow:hidden;">
                            <div style="height:100%;width:{{ $pct }}%;background:var(--ink-900);border-radius:999px;"></div>
                        </div>
                    @endif
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="{{ route('account.order', [$locale, $latestOrder->number]) }}" wire:navigate class="d-btn d-btn--dark">
                            {{ __('account.overview.view_order') }}<x-icon name="arrow-right" size="18" />
                        </a>
                        <a href="{{ route('track', $locale) }}?number={{ $latestOrder->number }}" class="d-btn d-btn--outline">
                            <x-icon name="package-search" size="18" />{{ __('account.overview.track_delivery') }}
                        </a>
                    </div>
                @else
                    <x-eyebrow>{{ __('account.overview.latest_order') }}</x-eyebrow>
                    <p style="margin:0;font:500 15px/1.6 var(--font-body);color:var(--fg-2);">{{ __('account.overview.no_orders') }}</p>
                    <div>
                        <a href="{{ route('products', $locale) }}" class="d-btn d-btn--dark">{{ __('account.overview.browse') }}<x-icon name="arrow-right" size="18" /></a>
                    </div>
                @endif
            </div>

            {{-- Wishlist preview --}}
            <div class="d-account__box d-account__box--sm" style="gap:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <h2 style="margin:0;font:600 20px/1.25 var(--font-display);letter-spacing:var(--tracking-heading);">
                        {{ __('account.nav.wishlist') }} <span style="font:700 14px var(--font-body);color:var(--fg-3);">{{ $counts['wishlist'] }}</span>
                    </h2>
                    @if ($wishlist->isNotEmpty())
                        <a href="{{ route('account.wishlist', $locale) }}" wire:navigate style="font:800 13px var(--font-body);color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px;text-underline-offset:3px;">{{ __('account.overview.view_all') }}</a>
                    @endif
                </div>
                @forelse ($wishlist->take(3) as $p)
                    @php $pSlug = $p->getTranslation('slug', $locale); @endphp
                    <a href="{{ route('product.show', [$locale, $pSlug]) }}" wire:navigate
                        style="display:grid;grid-template-columns:56px minmax(0,1fr) auto;gap:12px;align-items:center;border-top:1px solid var(--border-1);padding-top:12px;text-decoration:none;color:var(--fg-1);">
                        <span style="width:56px;height:56px;border-radius:8px;border:1px solid var(--border-1);background:var(--white) center/contain no-repeat;@if ($p->getFirstMediaUrl('gallery')) background-image:url('{{ $p->getFirstMediaUrl('gallery') }}');@endif"></span>
                        <span style="display:grid;gap:3px;min-width:0;">
                            <span class="d-pcard__brand">{{ $p->brand?->name }}</span>
                            <span style="font:700 13px/1.35 var(--font-body);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $p->getTranslation('name', $locale) }}</span>
                        </span>
                        <b style="font:800 13px var(--font-body);white-space:nowrap;">{{ $p->min_price !== null ? number_format((float) $p->min_price, 0).' '.__('ui.egp') : __('account.wishlist.on_request') }}</b>
                    </a>
                @empty
                    <span style="font:500 14px/1.55 var(--font-body);color:var(--fg-2);">
                        {{ __('account.wishlist.empty_hint') }}
                        <a href="{{ route('products', $locale) }}" wire:navigate style="font-weight:800;color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px;text-underline-offset:3px;">{{ __('account.overview.browse') }}</a>
                    </span>
                @endforelse
            </div>
        </div>

        {{-- Quick tiles --}}
        <div class="d-account__tiles">
            @foreach ($tiles as [$url, $icon, $label, $desc])
                <a href="{{ $url }}" wire:navigate class="d-card d-account__tile">
                    <span class="d-nav__mi-ico"><x-icon :name="$icon" size="20" /></span>
                    <span style="display:grid;gap:3px;">
                        <b style="font:800 14px/1.3 var(--font-body);">{{ $label }}</b>
                        <span style="font:500 13px/1.4 var(--font-body);color:var(--fg-3);">{{ $desc }}</span>
                    </span>
                </a>
            @endforeach
            @php
                $waUrl = \Illuminate\Support\Facades\URL::signedRoute('wa.redirect', ['locale' => $locale, 'source' => 'order_help', 'ref_url' => request()->url()]);
            @endphp
            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="d-card d-account__tile" data-source="order_help">
                <span class="d-nav__mi-ico" style="background:var(--wa-100);color:var(--wa-600);"><x-icon name="message-circle" size="20" /></span>
                <span style="display:grid;gap:3px;">
                    <b style="font:800 14px/1.3 var(--font-body);">{{ __('account.help.title_short') }}</b>
                    <span style="font:500 13px/1.4 var(--font-body);color:var(--fg-3);">{{ __('account.help.reply_time') }}</span>
                </span>
            </a>
        </div>
    </x-site.account.shell>
</div>
