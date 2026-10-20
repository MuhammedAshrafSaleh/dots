@php
    /**
     * Filter sidebar — shared between desktop sidebar and mobile drawer.
     * @var bool $embedded  true = inside mobile drawer
     */
    $locale     = app()->getLocale();
    $brands     = $facets['brands']      ?? [];
    $attributes = $facets['attributes']  ?? [];
    $catCounts  = $facets['categories']  ?? [];

    $filteredBrands = $brandSearch
        ? array_values(array_filter($brands, fn ($b) => stripos($b['name'], $brandSearch) !== false))
        : $brands;
    $visibleBrands = $showAllBrands ? $filteredBrands : array_slice($filteredBrands, 0, 5);
@endphp

<div class="d-filters" x-data="{ catOpen: true, brandOpen: true, priceOpen: true, availOpen: true }">

    {{-- Header --}}
    <div class="d-filters__head">
        <h2><x-icon name="sliders-horizontal" :size="18" />{{ __('ui.listing_filters') }}</h2>
        @if ($activeCount > 0)
            <button type="button" class="d-fsec__more" wire:click="clearAll">
                {{ __('ui.listing_clear_all') }}
            </button>
        @endif
    </div>

    {{-- ── Category tree ── --}}
    @if ($catTree->isNotEmpty())
        <div class="d-fsec" :class="catOpen && 'd-fsec--open'">
            <button type="button" class="d-fsec__btn" @click="catOpen = !catOpen" :aria-expanded="catOpen">
                {{ __('ui.listing_category_filter') }}
                <x-icon name="chevron-down" :size="18" />
            </button>
            <div class="d-fsec__body" x-show="catOpen" x-data="{ showAllCats: false }">
                <ul class="d-tree">
                    <li>
                        <button type="button"
                                wire:click="$set('categoryId', null)"
                                aria-current="{{ $categoryId === null ? 'true' : 'false' }}">
                            <span>{{ __('ui.listing_all_cats') }}</span>
                        </button>
                    </li>
                    @foreach ($catTree as $catIdx => $cat)
                        @php
                            $catName  = $cat->getTranslation('name', $locale);
                            $catCount = $catCounts[$cat->id] ?? 0;
                        @endphp
                        <li x-show="showAllCats || {{ $catIdx }} < 1">
                            <button type="button"
                                    wire:click="$set('categoryId', {{ $cat->id }})"
                                    aria-current="{{ $categoryId === $cat->id ? 'true' : 'false' }}">
                                <span>{{ $catName }}</span>
                                @if ($catCount > 0)<span class="d-tree__count">{{ number_format($catCount) }}</span>@endif
                            </button>
                            @if ($cat->children->isNotEmpty())
                                <ul class="d-tree">
                                    @foreach ($cat->children as $child)
                                        @php
                                            $childName  = $child->getTranslation('name', $locale);
                                            $childCount = $catCounts[$child->id] ?? 0;
                                        @endphp
                                        <li>
                                            <button type="button"
                                                    wire:click="$set('categoryId', {{ $child->id }})"
                                                    aria-current="{{ $categoryId === $child->id ? 'true' : 'false' }}">
                                                <span>{{ $childName }}</span>
                                                @if ($childCount > 0)<span class="d-tree__count">{{ number_format($childCount) }}</span>@endif
                                            </button>
                                            @if ($child->children->isNotEmpty())
                                                <ul class="d-tree">
                                                    @foreach ($child->children as $leaf)
                                                        @php $leafCount = $catCounts[$leaf->id] ?? 0; @endphp
                                                        <li>
                                                            <button type="button"
                                                                    wire:click="$set('categoryId', {{ $leaf->id }})"
                                                                    aria-current="{{ $categoryId === $leaf->id ? 'true' : 'false' }}">
                                                                <span>{{ $leaf->getTranslation('name', $locale) }}</span>
                                                                @if ($leafCount > 0)<span class="d-tree__count">{{ number_format($leafCount) }}</span>@endif
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($catTree->count() > 1)
                    <button type="button" class="d-fsec__more" @click="showAllCats = !showAllCats">
                        <span x-show="!showAllCats">{{ __('ui.listing_show_all', ['count' => $catTree->count()]) }}</span>
                        <span x-show="showAllCats">{{ __('ui.listing_show_less') }}</span>
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Brands ── --}}
    @if (! empty($brands))
        <div class="d-fsec" :class="brandOpen && 'd-fsec--open'">
            <button type="button" class="d-fsec__btn" @click="brandOpen = !brandOpen" :aria-expanded="brandOpen">
                {{ __('ui.listing_manufacturer') }}
                <x-icon name="chevron-down" :size="18" />
            </button>
            <div class="d-fsec__body" x-show="brandOpen">
                <x-input
                    size="sm"
                    icon="search"
                    :placeholder="__('ui.listing_search_brands')"
                    wire:model.live.debounce.250ms="brandSearch"
                />
                @foreach ($visibleBrands as $brand)
                    <label class="d-check d-check--row">
                        <input type="checkbox"
                               wire:click="toggleBrand({{ $brand['id'] }})"
                               @checked(in_array($brand['id'], $brandIds)) />
                        <span>{{ $brand['name'] }}</span>
                        <span class="d-check__count">{{ number_format($brand['count']) }}</span>
                    </label>
                @endforeach
                @if (count($filteredBrands) > 5)
                    <button type="button" class="d-fsec__more" wire:click="$toggle('showAllBrands')">
                        @if ($showAllBrands)
                            {{ __('ui.listing_show_less') }}
                        @else
                            {{ __('ui.listing_show_all', ['count' => count($filteredBrands)]) }}
                        @endif
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Filterable attributes ── --}}
    @foreach ($attributes as $attr)
        <div class="d-fsec d-fsec--open" x-data="{ open: true }" :class="open && 'd-fsec--open'">
            <button type="button" class="d-fsec__btn" @click="open = !open" :aria-expanded="open">
                {{ $attr['name'] }}
                <x-icon name="chevron-down" :size="18" />
            </button>
            <div class="d-fsec__body" x-show="open">
                @if ($attr['display'] === 'swatch')
                    <div class="d-variant__opts" style="gap:12px;padding-inline:3px;">
                        @foreach ($attr['values'] as $v)
                            <button type="button"
                                    class="d-swatch"
                                    style="background:{{ $v['swatch'] ?? 'var(--ink-200)' }};"
                                    aria-pressed="{{ in_array($v['id'], $attrValueIds) ? 'true' : 'false' }}"
                                    aria-label="{{ $v['label'] }} ({{ $v['count'] }})"
                                    title="{{ $v['label'] }}"
                                    wire:click="toggleAttrValue({{ $v['id'] }})">
                            </button>
                        @endforeach
                    </div>
                @elseif ($attr['display'] === 'button')
                    <div class="d-variant__opts">
                        @foreach ($attr['values'] as $v)
                            <button type="button"
                                    class="d-opt"
                                    style="min-width:0;height:34px;padding-inline:10px;font-size:13px;"
                                    aria-pressed="{{ in_array($v['id'], $attrValueIds) ? 'true' : 'false' }}"
                                    wire:click="toggleAttrValue({{ $v['id'] }})">
                                {{ $v['label'] }}
                                @if ($v['count'] > 0)
                                    <span style="font-size:11px;font-weight:500;color:var(--fg-3);">({{ $v['count'] }})</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    @foreach ($attr['values'] as $v)
                        <label class="d-check d-check--row">
                            <input type="checkbox"
                                   wire:click="toggleAttrValue({{ $v['id'] }})"
                                   @checked(in_array($v['id'], $attrValueIds)) />
                            <span>{{ $v['label'] }}</span>
                            <span class="d-check__count">{{ number_format($v['count']) }}</span>
                        </label>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach

    {{-- ── Price range ── --}}
    <div class="d-fsec" :class="priceOpen && 'd-fsec--open'">
        <button type="button" class="d-fsec__btn" @click="priceOpen = !priceOpen" :aria-expanded="priceOpen">
            {{ __('ui.listing_price_range') }}
            <x-icon name="chevron-down" :size="18" />
        </button>
        <div class="d-fsec__body" x-show="priceOpen"
             x-data="{ min: {{ $priceMin ?? 'null' }}, max: {{ $priceMax ?? 'null' }} }">
            <div class="d-range">
                <input type="number"
                       class="d-input d-input--sm"
                       x-model.number="min"
                       :value="min"
                       @change="$wire.applyPriceRange(min || null, max || null)"
                       placeholder="{{ __('ui.listing_price_min') }}"
                       min="0"
                       aria-label="{{ __('ui.listing_price_min') }}" />
                <span style="color:var(--fg-3);">–</span>
                <input type="number"
                       class="d-input d-input--sm"
                       x-model.number="max"
                       :value="max"
                       @change="$wire.applyPriceRange(min || null, max || null)"
                       placeholder="{{ __('ui.listing_price_max') }}"
                       min="0"
                       aria-label="{{ __('ui.listing_price_max') }}" />
            </div>
        </div>
    </div>

    {{-- ── Availability ── --}}
    <div class="d-fsec" :class="availOpen && 'd-fsec--open'">
        <button type="button" class="d-fsec__btn" @click="availOpen = !availOpen" :aria-expanded="availOpen">
            {{ __('ui.listing_availability') }}
            <x-icon name="chevron-down" :size="18" />
        </button>
        <div class="d-fsec__body" x-show="availOpen">
            <label class="d-check d-check--row">
                <input type="checkbox" wire:model.live="inStock" />
                <span>{{ __('ui.listing_in_stock') }}</span>
            </label>
            <label class="d-check d-check--row">
                <input type="checkbox" wire:model.live="ships24h" />
                <span>{{ __('ui.listing_ships_24h') }}</span>
            </label>
        </div>
    </div>

</div>
