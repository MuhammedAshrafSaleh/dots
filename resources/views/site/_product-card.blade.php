@php
    /** @var \App\Models\Product $product */
    $productSlug = $product->getTranslation('slug', $locale);
    $productName = $product->getTranslation('name', $locale);
    $brandName = $product->brand?->name;
    $imgUrl = $product->getFirstMediaUrl('gallery');
@endphp
<div class="d-rv">
    <article class="d-card d-pcard">
        <div class="d-pcard__media">
            <x-image-frame :src="$imgUrl" :alt="$productName" style="height:100%;" />
            <div class="d-pcard__badges">
                @if ($product->is_new)
                    <x-badge variant="accent">{{ __('ui.tab_new') }}</x-badge>
                @elseif ($product->is_best_seller)
                    <x-badge variant="dark">{{ __('ui.tab_best') }}</x-badge>
                @elseif ($product->is_smart_pick)
                    <x-badge>{{ __('ui.tab_smart') }}</x-badge>
                @endif
            </div>
        </div>
        <div class="d-card__body">
            @if ($brandName)
                <span class="d-pcard__brand">{{ $brandName }}</span>
            @endif
            <h3 class="d-pcard__title">{{ $productName }}</h3>
            @if ($product->sku)
                <span class="d-pcard__sku">{{ $product->sku }}</span>
            @endif
            <div class="d-pcard__price">
                @if ($product->price_on_request)
                    <span class="d-pcard__por">{{ __('ui.price_on_request') }}</span>
                @elseif ($product->min_price)
                    <span class="d-pcard__now">{{ number_format((float) $product->min_price, 0) }} {{ __('ui.egp') }}</span>
                    @if ($product->max_compare_at && $product->max_compare_at > $product->min_price)
                        <span class="d-pcard__was">{{ number_format((float) $product->max_compare_at, 0) }}</span>
                    @endif
                @endif
            </div>
        </div>
        <a href="{{ route('products') }}" class="d-card__link" aria-label="{{ $productName }}"></a>
    </article>
</div>
