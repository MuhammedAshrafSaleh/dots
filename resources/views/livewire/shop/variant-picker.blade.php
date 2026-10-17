@php
    /**
     * @var \App\Models\Product $product
     * @var list<array{id:int,name:string,display:string,values:list<array{id:int,label:string,swatch:string|null}>}> $axes
     * @var int[] $disabled
     * @var \App\Models\ProductVariant|null $selectedVariant
     * @var bool $hasPrice
     * @var string|null $price
     * @var string|null $compareAt
     * @var string|null $sku
     * @var \App\Enums\StockStatus|null $stockStatus
     * @var int|null $stockQty
     * @var bool $canAddToCart
     */
    use App\Enums\StockStatus;

    $locale      = app()->getLocale();
    $productName = $product->getTranslation('name', $locale);
    $brandName   = $product->brand?->name;
    $brandUrl    = $product->brand ? route('brand.show', [$locale, $product->brand->slug]) : null;
    $warranty    = $product->getTranslation('warranty', $locale);

    $stockTone  = match ($stockStatus) {
        StockStatus::In    => 'success',
        StockStatus::Low   => 'warning',
        default            => 'neutral',
    };
    $stockLabel = match (true) {
        $stockStatus === StockStatus::In  && $stockQty !== null => __('ui.pdp_in_stock_n',  ['n' => number_format($stockQty)]),
        $stockStatus === StockStatus::Low && $stockQty !== null => __('ui.pdp_low_stock_n', ['n' => $stockQty]),
        $stockStatus === StockStatus::In                        => __('ui.pcard_in_stock'),
        $stockStatus === StockStatus::Low                       => __('ui.pcard_low_stock'),
        $stockStatus === StockStatus::Order                     => __('ui.pdp_on_order'),
        default                                                 => __('ui.pdp_out_of_stock'),
    };

    $salePct = null;
    if ($hasPrice && $compareAt && (float) $compareAt > (float) $price) {
        $salePct = (int) round((1 - (float) $price / (float) $compareAt) * 100);
    }
@endphp

<div>{{-- Livewire root --}}

    {{-- Brand + name + meta --}}
    <div class="d-pdp__meta">
        @if ($brandName && $brandUrl)
            <a href="{{ $brandUrl }}" class="d-pcard__brand"
               style="text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                {{ $brandName }}<x-icon name="arrow-right" size="12" />
            </a>
        @elseif ($brandName)
            <span class="d-pcard__brand">{{ $brandName }}</span>
        @endif

        <h1 class="t-h2" style="margin:0;">{{ $productName }}</h1>

        <div class="d-pdp__badges-row">
            @if ($sku)
                <span class="d-pcard__sku">SKU {{ $sku }}</span>
            @endif
            <x-badge :tone="$stockTone" dot>{{ $stockLabel }}</x-badge>
            @if ($product->is_dimmable)
                <x-badge tone="outline">{{ __('ui.pdp_dimmable') }}</x-badge>
            @endif
            @if ($salePct)
                <span class="d-badge d-badge--sale">{{ __('ui.pcard_sale_off', ['pct' => $salePct]) }}</span>
            @elseif ($product->is_new)
                <x-badge tone="accent">{{ __('ui.tab_new') }}</x-badge>
            @elseif ($product->is_best_seller)
                <x-badge tone="dark">{{ __('ui.tab_best') }}</x-badge>
            @endif
        </div>
    </div>

    {{-- Price block --}}
    <div class="d-pdp__price-row">
        <div style="display:flex;align-items:baseline;gap:10px;">
            @if ($hasPrice)
                <span class="d-pdp__price-now">{{ number_format((float) $price, 0) }} {{ __('ui.egp') }}</span>
                @if ($compareAt && (float) $compareAt > (float) $price)
                    <span class="d-pcard__was" style="font-size:16px;">{{ number_format((float) $compareAt, 0) }}</span>
                @endif
            @else
                <span class="d-pcard__por">{{ __('ui.price_on_request') }}</span>
            @endif
        </div>
        @if ($hasPrice)
            <span class="d-pdp__vat">{{ __('ui.pdp_vat_note') }}</span>
        @endif
    </div>

    {{-- Variant pickers --}}
    @php
        $optAxisCount = collect($axes)->filter(fn($a) => $a['display'] !== 'swatch')->count();
    @endphp
    <div class="d-pdp__variants">
    @foreach ($axes as $axis)
        @php
            $curVal = collect($axis['values'])->firstWhere('id', $selected[$axis['id']] ?? null);
            $isWide = $axis['display'] === 'swatch' || $optAxisCount <= 1;
        @endphp
        <div class="d-variant{{ $isWide ? ' d-variant--wide' : '' }}" role="group" aria-label="{{ $axis['name'] }}">
            <div class="d-variant__head-row">
                <span class="d-variant__head">
                    {{ $axis['name'] }}
                    @if ($curVal)
                        <span>{{ $curVal['label'] }}</span>
                    @endif
                </span>
                @if ($axis['display'] === 'swatch' && $curVal && $curVal['swatch'])
                    <div class="d-variant__preview" style="background:{{ $curVal['swatch'] }};"></div>
                @endif
            </div>
            <div class="d-variant__opts">
                @foreach ($axis['values'] as $val)
                    @php
                        $isSelected = isset($selected[$axis['id']]) && (int) $selected[$axis['id']] === $val['id'];
                        $isOff      = in_array($val['id'], $disabled, true);
                    @endphp
                    @if ($axis['display'] === 'swatch')
                        <button type="button"
                            wire:click="select({{ $axis['id'] }}, {{ $val['id'] }})"
                            class="d-swatch"
                            @if ($val['swatch']) style="background:{{ $val['swatch'] }}" @endif
                            title="{{ $val['label'] }}"
                            aria-label="{{ $val['label'] }}"
                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                            @if ($isOff) disabled aria-disabled="true" @endif
                        ></button>
                    @else
                        <button type="button"
                            wire:click="select({{ $axis['id'] }}, {{ $val['id'] }})"
                            class="d-opt"
                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                            @if ($isOff) disabled aria-disabled="true" @endif
                        >{{ $val['label'] }}</button>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
    </div>

    {{-- Qty stepper + actions --}}
    <div class="d-pdp__actions">

        {{-- Qty stepper --}}
        <div class="d-stepper" role="group" aria-label="{{ __('ui.quantity') }}">
            <button type="button"
                wire:click="decrementQty"
                aria-label="{{ __('ui.decrease_quantity') }}"
                @if ($qty <= 1) disabled @endif
            >
                <x-icon name="minus" size="16" />
            </button>
            <input type="number" inputmode="numeric"
                value="{{ $qty }}"
                min="1" max="99"
                aria-label="{{ __('ui.quantity') }}"
                readonly>
            <button type="button"
                wire:click="incrementQty"
                aria-label="{{ __('ui.increase_quantity') }}"
            >
                <x-icon name="plus" size="16" />
            </button>
        </div>

        @if ($hasPrice)
            <button type="button"
                wire:click="addToCart"
                wire:loading.attr="disabled"
                class="d-btn d-btn--primary d-btn--lg"
                style="flex:1 1 180px;"
                @if (! $canAddToCart) disabled aria-disabled="true" @endif
            >
                <x-icon name="shopping-bag" size="18" />
                {{ __('ui.pdp_add_to_cart') }}
            </button>

            <x-whatsapp-button
                source="product"
                :model="$product"
                :variant="$selectedVariant"
                :qty="$qty"
                :label="__('ui.pdp_request_quote')"
                style="flex:1 1 auto;" />
        @else
            <x-whatsapp-button
                source="product"
                :model="$product"
                :variant="$selectedVariant"
                :qty="$qty"
                :label="__('ui.pdp_request_quote')"
                style="flex:1 1 auto;" />
        @endif

        {{-- Wishlist heart --}}
        <span x-data="{ tip: false }" style="position:relative;display:inline-flex;flex:none;">
            @auth('customer')
                <button type="button"
                    class="d-btn d-btn--outline d-btn--icon d-btn--lg"
                    aria-pressed="false"
                    aria-label="{{ __('ui.pdp_save_wishlist') }}"
                    title="{{ __('ui.pdp_save_wishlist') }}">
                    <x-icon name="heart" size="20" />
                </button>
            @else
                <button type="button"
                    class="d-btn d-btn--outline d-btn--icon d-btn--lg"
                    @click.stop="tip = !tip"
                    :aria-pressed="tip ? 'true' : 'false'"
                    aria-label="{{ __('ui.pdp_save_wishlist') }}"
                    title="{{ __('ui.pdp_save_wishlist') }}">
                    <x-icon name="heart" size="20" />
                </button>
                <div x-show="tip"
                    x-cloak
                    @click.outside="tip = false"
                    x-effect="if (tip) { setTimeout(() => tip = false, 6000) }"
                    class="d-favtip"
                    style="top:calc(100% + 10px);inset-inline-end:0;max-width:none;"
                    role="dialog"
                    aria-label="{{ __('ui.pdp_wishlist_title') }}">
                    <div class="d-favtip__head">
                        <x-icon name="heart" size="16" />
                        <b>{{ __('ui.pdp_wishlist_title') }}</b>
                        <button type="button" class="d-favtip__x"
                            @click.stop="tip = false"
                            aria-label="{{ __('ui.close') }}">
                            <x-icon name="x" size="16" />
                        </button>
                    </div>
                    <span>{{ __('ui.pdp_wishlist_body') }}</span>
                    <div class="d-favtip__acts">
                        <a href="#" class="d-btn d-btn--sm d-btn--primary">{{ __('ui.login') }}</a>
                        <a href="#" class="d-favtip__link">{{ __('ui.create_account') }}</a>
                    </div>
                </div>
            @endauth
        </span>

    </div>

    {{-- Trust row --}}
    <div class="d-trust">
        @if ($product->ships_24h)
            <span><x-icon name="truck" size="18" />{{ __('ui.pdp_ships_24h') }}</span>
        @endif
        <span><x-icon name="wrench" size="18" />{{ __('ui.pdp_installation') }}</span>
        @if ($warranty)
            <span><x-icon name="shield-check" size="18" />{{ __('ui.pdp_warranty_label', ['w' => $warranty]) }}</span>
        @endif
    </div>

</div>
