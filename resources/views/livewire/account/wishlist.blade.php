@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Product> $products */
    /** @var int $buyableCount */
    /** @var array{orders:int,wishlist:int,addresses:int} $counts */
    /** @var string $locale */
@endphp

<div>
    <x-site.account.shell current="wishlist" :counts="$counts" :heading="__('account.nav.wishlist')">
        @if (session('account.flash'))
            <div class="d-auth__alert d-auth__alert--success" role="status" wire:key="flash">
                <span class="d-auth__alert-icon"><x-icon name="check" size="18" /></span>
                <div style="padding-top:5px;"><b>{{ session('account.flash') }}</b></div>
            </div>
        @endif

        @if ($products->isNotEmpty())
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <span style="font:600 14px var(--font-body);color:var(--fg-2);">{{ $products->count() }} {{ __('account.wishlist.saved_items') }}</span>
                @if ($buyableCount > 1)
                    <button type="button" wire:click="addAll" class="d-btn d-btn--outline d-btn--sm">
                        <x-icon name="shopping-bag" size="16" />{{ __('account.wishlist.add_all') }}
                    </button>
                @endif
            </div>
            <div class="d-account__wishgrid">
                @foreach ($products as $p)
                    <div wire:key="wish-{{ $p->id }}" style="display:grid;gap:8px;align-content:start;">
                        @include('site._product-card', ['product' => $p])
                        <button type="button" wire:click="remove({{ $p->id }})" class="d-btn d-btn--ghost d-btn--sm">
                            <x-icon name="trash-2" size="16" />{{ __('account.wishlist.remove') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="d-account__empty">
                <span class="d-account__empty-ico"><x-icon name="heart" size="28" /></span>
                <h2>{{ __('account.wishlist.empty_title') }}</h2>
                <p>{{ __('account.wishlist.empty_body') }}</p>
                <div style="margin-top:6px;">
                    <a href="{{ route('products', $locale) }}" class="d-btn d-btn--dark">{{ __('account.overview.browse') }}<x-icon name="arrow-right" size="18" /></a>
                </div>
            </div>
        @endif
    </x-site.account.shell>
</div>
