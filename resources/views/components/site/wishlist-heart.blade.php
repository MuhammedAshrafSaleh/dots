@props([
    'product',
    'variant' => 'card', // 'card' (absolute heart on a product card) or 'pdp' (outline button)
])

@php
    use App\Support\Wishlist;

    /** @var \App\Models\Product $product */
    $productId = $product->id;
    $isSaved = auth('customer')->check() && app(Wishlist::class)->has($productId);

    $isPdp = $variant === 'pdp';
    $btnClass = $isPdp ? 'd-btn d-btn--outline d-btn--icon d-btn--lg d-wish-btn' : 'd-pcard__fav';
    $iconSize = $isPdp ? 20 : 18;
    $label = $isPdp ? __('ui.pdp_save_wishlist') : __('ui.pcard_save_wishlist');
    $title = __('ui.pcard_wishlist_title');
    $body = $isPdp ? __('ui.pdp_wishlist_body') : __('ui.pcard_wishlist_body');
    $removeLabel = __('ui.pcard_remove_wishlist');

    $locale = app()->getLocale();
    $loginUrl = route('login', $locale);
    $registerUrl = route('register', $locale);
@endphp

@auth('customer')
    <button type="button"
        class="{{ $btnClass }}"
        x-data="{ saved: @js($isSaved) }"
        x-on:click.stop.prevent="saved = !saved; Livewire.dispatch('wishlist-toggle', { productId: {{ $productId }} })"
        x-bind:aria-pressed="saved ? 'true' : 'false'"
        x-bind:aria-label="saved ? @js($removeLabel) : @js($label)"
        x-bind:title="saved ? @js($removeLabel) : @js($label)">
        <x-icon name="heart" :size="$iconSize" />
    </button>
@else
    <span @class(['d-wish-wrap' => $isPdp]) x-data="{ tip: false }" @if ($isPdp) style="position:relative;display:inline-flex;flex:none;" @endif>
        <button type="button"
            class="{{ $btnClass }}"
            x-on:click.stop.prevent="tip = !tip"
            x-bind:aria-pressed="tip ? 'true' : 'false'"
            aria-label="{{ $label }}"
            title="{{ $label }}">
            <x-icon name="heart" :size="$iconSize" />
        </button>
        <div x-show="tip"
            x-cloak
            x-on:click.outside="tip = false"
            x-effect="if (tip) { setTimeout(() => tip = false, 6000) }"
            class="d-favtip"
            @if ($isPdp) style="top:calc(100% + 10px);inset-inline-end:0;max-width:none;" @endif
            role="dialog"
            aria-label="{{ $title }}">
            <div class="d-favtip__head">
                <x-icon name="heart" size="16" />
                <b>{{ $title }}</b>
                <button type="button" class="d-favtip__x" x-on:click.stop="tip = false" aria-label="{{ __('ui.close') }}">
                    <x-icon name="x" size="16" />
                </button>
            </div>
            <span>{{ $body }}</span>
            <div class="d-favtip__acts">
                <a href="{{ $loginUrl }}" class="d-btn d-btn--sm d-btn--primary">{{ __('ui.login') }}</a>
                <a href="{{ $registerUrl }}" class="d-favtip__link">{{ __('ui.create_account') }}</a>
            </div>
        </div>
    </span>
@endauth
