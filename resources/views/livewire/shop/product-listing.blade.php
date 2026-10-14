@php
    /**
     * @var \Illuminate\Pagination\LengthAwarePaginator $products
     * @var array{brands: list<array{id:int,name:string,count:int}>, attributes: list<array{id:int,name:string,display:string,values:list<array{id:int,label:string,swatch:string|null,count:int}>}>} $facets
     * @var \Illuminate\Support\Collection $catTree
     * @var int $activeCount
     * @var \App\Models\Category|null $currentCat
     */
    $locale    = app()->getLocale();
    $totalCount = $products->total();
    $pageTitle  = $currentCat
        ? $currentCat->getTranslation('name', $locale)
        : __('ui.all_products');

    $breadcrumbs = array_values(array_filter([
        ['label' => __('ui.home'), 'url' => route('home', $locale)],
        $currentCat?->parent ? [
            'label' => $currentCat->parent->getTranslation('name', $locale),
            'url'   => route('products', $locale).'?cat='.$currentCat->parent_id,
        ] : null,
        ['label' => $pageTitle],
    ]));

    $brands    = $facets['brands']    ?? [];
    $attributes = $facets['attributes'] ?? [];

    $filteredBrands = $brandSearch
        ? array_values(array_filter($brands, fn ($b) => stripos($b['name'], $brandSearch) !== false))
        : $brands;
    $visibleBrands = $showAllBrands ? $filteredBrands : array_slice($filteredBrands, 0, 5);

    // Active filter chips for the "ActiveFilters" row
    $chips = [];
    foreach ($brandIds as $bid) {
        $bname = collect($brands)->firstWhere('id', $bid)['name'] ?? $bid;
        $chips[] = ['id' => 'b'.$bid, 'type' => 'brand', 'key' => $bid, 'facet' => __('ui.listing_manufacturer'), 'label' => $bname];
    }
    foreach ($attrValueIds as $avid) {
        foreach ($attributes as $attr) {
            foreach ($attr['values'] as $v) {
                if ($v['id'] === $avid) {
                    $chips[] = ['id' => 'a'.$avid, 'type' => 'attr', 'key' => $avid, 'facet' => $attr['name'], 'label' => $v['label']];
                }
            }
        }
    }
    if ($inStock) {
        $chips[] = ['id' => 'instock', 'type' => 'instock', 'key' => null, 'facet' => __('ui.listing_availability'), 'label' => __('ui.listing_in_stock')];
    }
    if ($ships24h) {
        $chips[] = ['id' => 'fast', 'type' => 'fast', 'key' => null, 'facet' => __('ui.listing_availability'), 'label' => __('ui.listing_ships_24h')];
    }
    if ($priceMin !== null || $priceMax !== null) {
        $pLabel = ($priceMin ? number_format($priceMin).' ' : '') . '–' . ($priceMax ? ' '.number_format($priceMax) : '');
        $chips[] = ['id' => 'price', 'type' => 'price', 'key' => null, 'facet' => __('ui.listing_price_range'), 'label' => trim($pLabel, ' –')];
    }
@endphp

<div>{{-- Livewire root --}}

{{-- ═══════════════════════════════ PAGE HEAD ══════════════════════════════ --}}
<div class="d-phead">
    <div class="d-container d-phead__inner">
        <x-breadcrumbs :items="$breadcrumbs" />
        <div class="d-phead__row">
            <h1 class="d-phead__h1">
                {{ $pageTitle }}
                <span class="d-phead__count">{{ number_format($totalCount) }}</span>
            </h1>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════ MOBILE FILTER TRIGGER ═════════════════ --}}
<div class="d-container" style="padding-top:16px;" x-data="{ drawerOpen: false }">

    {{-- ── Mobile: filter button ── --}}
    <div class="d-filter-toggle" style="margin-bottom:12px;">
        <button type="button" class="d-btn d-btn--outline d-filter-toggle" @click="drawerOpen = true">
            <x-icon name="sliders-horizontal" :size="16" />
            {{ __('ui.listing_filter_btn', ['count' => $activeCount]) }}
        </button>
    </div>

    {{-- Mobile filter overlay + drawer --}}
    <div class="d-filter-overlay" :class="{ 'is-open': drawerOpen }" @click="drawerOpen = false" x-cloak></div>
    <div class="d-filter-drawer" :class="{ 'is-open': drawerOpen }" x-cloak>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <span style="font:800 16px/1 var(--font-body);">{{ __('ui.listing_filters') }}</span>
            <button type="button" class="d-icon-btn" @click="drawerOpen = false" aria-label="{{ __('ui.close') }}">
                <x-icon name="x" :size="20" />
            </button>
        </div>
        @include('livewire.shop._filter-sidebar', ['embedded' => true])
    </div>

    {{-- ════════════════════════════ MAIN LAYOUT ══════════════════════════════ --}}
    <div class="d-listing">

        {{-- ── Desktop sidebar ── --}}
        <aside class="d-listing__sidebar">
            @include('livewire.shop._filter-sidebar', ['embedded' => false])
        </aside>

        {{-- ── Content column ── --}}
        <div class="d-listing__content" wire:loading.class="is-loading">

            {{-- Toolbar --}}
            <div class="d-listing__toolbar">
                {{-- Mobile filter button (inside content column, compact only) --}}
                <button type="button" class="d-btn d-btn--outline d-filter-toggle" @click="drawerOpen = true">
                    <x-icon name="sliders-horizontal" :size="16" />
                    {{ __('ui.listing_filter_btn', ['count' => $activeCount]) }}
                </button>

                {{-- Sort dropdown --}}
                <div class="d-control" style="flex:1;min-width:160px;max-width:240px;">
                    <select wire:model.live="sort"
                            class="d-input d-select d-input--sm"
                            aria-label="{{ __('ui.listing_sort_recommended') }}">
                        <option value="recommended">{{ __('ui.listing_sort_recommended') }}</option>
                        <option value="price_asc">{{ __('ui.listing_sort_price_asc') }}</option>
                        <option value="price_desc">{{ __('ui.listing_sort_price_desc') }}</option>
                        <option value="newest">{{ __('ui.listing_sort_newest') }}</option>
                    </select>
                    <span class="d-control__trail"><x-icon name="chevron-down" :size="18" /></span>
                </div>

                {{-- Grid / list toggle (desktop) --}}
                <div style="margin-inline-start:auto;display:flex;gap:4px;" class="d-compact-hide">
                    <button type="button"
                            wire:click="$set('view', 'grid')"
                            class="d-icon-btn"
                            aria-pressed="{{ $view === 'grid' ? 'true' : 'false' }}"
                            aria-label="{{ __('ui.listing_view_grid') }}">
                        <x-icon name="grid-2x2" :size="18" />
                    </button>
                    <button type="button"
                            wire:click="$set('view', 'list')"
                            class="d-icon-btn"
                            aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"
                            aria-label="{{ __('ui.listing_view_list') }}">
                        <x-icon name="list" :size="18" />
                    </button>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if ($chips)
                <div class="d-active" style="margin-bottom:16px;">
                    <span style="font:800 13px var(--font-body);margin-inline-end:4px;">
                        {{ number_format($totalCount) }} {{ __('ui.products') }}
                    </span>
                    @foreach ($chips as $chip)
                        <span class="d-chip d-chip--active">
                            <span class="d-chip__key">{{ $chip['facet'] }}:</span>
                            {{ $chip['label'] }}
                            <button type="button" class="d-chip__x"
                                    aria-label="{{ __('ui.remove_filter') }}"
                                    @if ($chip['type'] === 'brand')   wire:click="toggleBrand({{ $chip['key'] }})"
                                    @elseif ($chip['type'] === 'attr') wire:click="toggleAttrValue({{ $chip['key'] }})"
                                    @elseif ($chip['type'] === 'instock') wire:click="$set('inStock', false)"
                                    @elseif ($chip['type'] === 'fast')    wire:click="$set('ships24h', false)"
                                    @elseif ($chip['type'] === 'price')   wire:click="applyPriceRange(null, null)"
                                    @endif>
                                <x-icon name="x" :size="14" stroke="2" />
                            </button>
                        </span>
                    @endforeach
                    <button type="button" class="d-active__clear" wire:click="clearAll">
                        {{ __('ui.listing_clear_all') }}
                    </button>
                </div>
            @endif

            {{-- Products --}}
            @if ($products->isEmpty())
                <div class="d-listing__empty">
                    <x-icon name="search-x" :size="48" />
                    <p class="t-body-lg" style="margin:0;">{{ __('ui.listing_no_results') }}</p>
                    <p class="t-body" style="margin:0;color:var(--fg-3);">{{ __('ui.listing_no_results_hint') }}</p>
                    @if ($activeCount > 0)
                        <button type="button" class="d-btn d-btn--outline" wire:click="clearAll">
                            {{ __('ui.listing_clear_all') }}
                        </button>
                    @endif
                </div>
            @else
                <div class="{{ $view === 'list' ? 'd-plist' : 'd-pgrid' }}">
                    @foreach ($products as $product)
                        @include('site._product-card', ['product' => $product])
                    @endforeach
                </div>

                <div style="padding-top:24px;border-top:1px solid var(--border-1);margin-top:20px;">
                    {{ $products->links() }}
                </div>
            @endif

        </div>{{-- /.d-listing__content --}}
    </div>{{-- /.d-listing --}}

</div>{{-- /.d-container (x-data) --}}
</div>{{-- /Livewire root --}}
