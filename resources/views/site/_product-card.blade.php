@php
    /** @var \App\Models\Product $product */
    use App\Enums\StockStatus;

    $productSlug    = $product->getTranslation('slug', $locale);
    $productName    = $product->getTranslation('name', $locale);
    $brandName      = $product->brand?->name;
    $imgUrl         = $product->getFirstMediaUrl('gallery');
    $productUrl     = route('product.show', [$locale, $productSlug]);

    // Sale badge: auto-computed
    $salePct = null;
    if (
        $product->min_price &&
        $product->max_compare_at &&
        $product->max_compare_at > $product->min_price
    ) {
        $salePct = (int) round((1 - (float) $product->min_price / (float) $product->max_compare_at) * 100);
    }

    // Color swatches from eager-loaded variants
    $swatches       = $product->cardSwatches();
    $variantsSummary = $product->getTranslation('variants_summary', $locale);

    // Stock label
    $stockMap = [
        StockStatus::In->value    => ['label' => __('ui.pcard_in_stock'),  'color' => 'var(--success-600)'],
        StockStatus::Low->value   => ['label' => __('ui.pcard_low_stock'), 'color' => 'var(--warning-600)'],
        StockStatus::Order->value => ['label' => __('ui.pcard_on_order'),  'color' => 'var(--ink-600)'],
    ];
    $stockInfo = $stockMap[$product->stock_status->value] ?? $stockMap[StockStatus::In->value];

    $hasPrice = ! $product->price_on_request && $product->min_price !== null;

    // Default variant for quick add-to-cart (full selection lives on the PDP).
    $defaultVariantId = null;
    if ($hasPrice) {
        $dv = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first(fn ($v) => $v->is_active && $v->price !== null)
            ?? $product->variants->first();
        $defaultVariantId = $dv?->id;
    }
@endphp

<article x-data="{ tip: false }" class="d-card d-pcard">

    {{-- Main link --}}
    <a href="{{ $productUrl }}" class="d-card__link" aria-label="{{ $productName }}"></a>

    {{-- Media --}}
    <div class="d-pcard__media">
        <x-image-frame :src="$imgUrl" :alt="$productName" />
        <div class="d-pcard__badges">
            @if ($salePct)
                <span class="d-badge d-badge--sale">{{ __('ui.pcard_sale_off', ['pct' => $salePct]) }}</span>
            @elseif ($product->is_new)
                <x-badge tone="accent">{{ __('ui.tab_new') }}</x-badge>
            @elseif ($product->is_best_seller)
                <x-badge tone="dark">{{ __('ui.tab_best') }}</x-badge>
            @elseif ($product->is_smart_pick)
                <x-badge>{{ __('ui.tab_smart') }}</x-badge>
            @endif
            @if ($product->price_on_request)
                <x-badge tone="neutral">{{ __('ui.price_on_request') }}</x-badge>
            @endif
        </div>
    </div>

    {{-- Wishlist heart (customer guard; Phase 8 wires actual saving) --}}
    @auth('customer')
        <button type="button"
            class="d-pcard__fav"
            aria-label="{{ __('ui.pcard_save_wishlist') }}"
            aria-pressed="false">
            <x-icon name="heart" size="18" />
        </button>
    @else
        <button type="button"
            class="d-pcard__fav"
            @click.stop="tip = !tip"
            :aria-pressed="tip"
            aria-label="{{ __('ui.pcard_save_wishlist') }}">
            <x-icon name="heart" size="18" />
        </button>
        <div x-show="tip"
            x-cloak
            @click.outside="tip = false"
            x-effect="if (tip) { setTimeout(() => tip = false, 6000) }"
            class="d-favtip"
            role="dialog"
            aria-label="{{ __('ui.pcard_wishlist_title') }}">
            <div class="d-favtip__head">
                <x-icon name="heart" size="16" />
                <b>{{ __('ui.pcard_wishlist_title') }}</b>
                <button type="button" class="d-favtip__x" @click.stop="tip = false" aria-label="{{ __('ui.close') }}">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <span>{{ __('ui.pcard_wishlist_body') }}</span>
            <div class="d-favtip__acts">
                {{-- Phase 8 will replace '#' with real customer auth routes --}}
                <a href="#" class="d-btn d-btn--sm d-btn--primary">{{ __('ui.login') }}</a>
                <a href="#" class="d-favtip__link">{{ __('ui.create_account') }}</a>
            </div>
        </div>
    @endauth

    {{-- Card body --}}
    <div class="d-card__body">
        @if ($brandName)
            <span class="d-pcard__brand">{{ $brandName }}</span>
        @endif
        <h3 class="d-pcard__title">{{ $productName }}</h3>
        @if ($product->sku)
            <span class="d-pcard__sku">{{ $product->sku }}</span>
        @endif
        @if ($swatches || $variantsSummary)
            <div class="d-pcard__meta">
                @if ($swatches)
                    <span class="d-pcard__dots">
                        @foreach ($swatches as $swatch)
                            <i style="background: {{ $swatch }}"></i>
                        @endforeach
                    </span>
                @endif
                @if ($variantsSummary)
                    <span>{{ $variantsSummary }}</span>
                @endif
            </div>
        @endif
        <div class="d-pcard__price">
            @if ($hasPrice)
                <span class="d-pcard__now">{{ number_format((float) $product->min_price, 0) }} {{ __('ui.egp') }}</span>
                @if ($product->max_compare_at && $product->max_compare_at > $product->min_price)
                    <span class="d-pcard__was">{{ number_format((float) $product->max_compare_at, 0) }}</span>
                @endif
            @else
                <span class="d-pcard__por">{{ __('ui.price_on_request') }}</span>
            @endif
        </div>
        <span class="d-pcard__stock" style="color: {{ $stockInfo['color'] }}">
            <i></i>{{ $stockInfo['label'] }}
        </span>
    </div>

    {{-- Actions --}}
    <div class="d-pcard__actions">
        @if ($hasPrice)
            <button type="button" class="d-btn d-btn--primary d-btn--sm d-card__over"
                @if ($defaultVariantId)
                    x-on:click.prevent="Livewire.dispatch('cart-add', { variantId: {{ $defaultVariantId }}, qty: 1 })"
                @else
                    disabled aria-disabled="true"
                @endif>
                <x-icon name="shopping-bag" size="16" />
                {{ __('ui.pcard_add_to_cart') }}
            </button>
            <x-whatsapp-button
                source="product"
                :model="$product"
                class="d-btn--sm d-btn--icon d-card__over"
                :label="__('ui.request_quote')"
                :icon-only="true" />
        @else
            <x-whatsapp-button
                source="product"
                :model="$product"
                :label="__('ui.request_quote')"
                class="d-btn--sm d-btn--block d-card__over" />
        @endif
    </div>

</article>
