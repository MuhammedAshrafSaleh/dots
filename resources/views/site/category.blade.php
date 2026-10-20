@php
    /** @var \App\Models\Category $category */
    /** @var \Illuminate\Support\Collection $children */
    /** @var int $totalCount */
    /** @var \Illuminate\Support\Collection $featuredProducts */
    /** @var \Illuminate\Support\Collection $faqs */
    /** @var \Illuminate\Support\Collection $brands */

    $heroMedia  = $category->getFirstMedia('hero');
    $catName    = $category->getTranslation('name', $locale);
    $catDesc    = $category->getTranslation('description', $locale);
    $guideTitle = $category->getTranslation('buying_guide_title', $locale);

    $parentCat  = $category->parent;

    $breadcrumbs = array_values(array_filter([
        ['label' => __('ui.home'), 'url' => route('home')],
        $parentCat ? [
            'label' => $parentCat->getTranslation('name', $locale),
            'url'   => route('category', [$locale, $parentCat->getTranslation('slug', $locale)]),
        ] : null,
        ['label' => $catName],
    ]));

    $faqItems = $faqs->map(fn ($f) => [
        'id'      => (string) $f->id,
        'title'   => $f->getTranslation('question', $locale),
        'content' => $f->getTranslation('answer', $locale),
    ])->values()->all();

    $showGuide = $guideTitle || $brands->isNotEmpty() || count($faqItems) > 0;
@endphp
<x-layouts.app :title="$category->getTranslation('meta_title', $locale) ?: $catName">
<x-seo
    :title="$category->getTranslation('meta_title', $locale) ?: $catName"
    :description="$category->getTranslation('meta_description', $locale) ?: $catDesc"
    :image="$heroMedia?->getUrl()"
/>

    {{-- ========== 1. HERO ========== --}}
    <section class="d-cat-hero" style="position:relative;min-height:440px;display:flex;align-items:flex-end;">
        <x-image-frame :media="$heroMedia" sizes="100vw" :alt="$catName" style="position:absolute;inset:0;" />
        {{-- Directional gradient: dark on the reading-start side (RTL flips direction) --}}
        <div aria-hidden="true" style="position:absolute;inset:0;background:linear-gradient({{ $dir === 'rtl' ? '270deg' : '90deg' }},rgba(24,24,22,.78),rgba(24,24,22,.1) 75%);"></div>

        <div class="d-container" style="position:relative;width:100%;padding-block:48px;color:#fff;display:grid;gap:14px;">
            {{-- White breadcrumb: override CSS vars for the crumbs palette --}}
            <div style="--fg-1:#fff;--fg-2:rgba(255,255,255,.85);--ink-300:rgba(255,255,255,.45);">
                <x-breadcrumbs :items="$breadcrumbs" />
            </div>

            <h1 class="t-h1" style="margin:0;max-width:700px;">{{ $catName }}</h1>

            @if ($catDesc)
                <p class="t-body-lg" style="margin:0;max-width:560px;color:rgba(255,255,255,.88);">{{ $catDesc }}</p>
            @endif

            <div style="margin-top:4px;">
                <a href="{{ route('products', $locale) }}?cat={{ $category->id }}" class="d-btn d-btn--dark">
                    {{ __('ui.cat_shop_all', ['count' => number_format($totalCount)]) }}
                    <x-icon name="arrow-right" :size="16" />
                </a>
            </div>
        </div>
    </section>

    {{-- ========== 2. SUB-CATEGORY TILES ========== --}}
    @if ($children->isNotEmpty())
        <section class="d-sec d-sec--tight">
            <div class="d-container">
                <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;" class="d-cat-subcats">
                    @foreach ($children as $i => $child)
                        @php
                            $childName  = $child->getTranslation('name', $locale);
                            $childSlug  = $child->getTranslation('slug', $locale);
                            $childImg   = $child->getFirstMedia('image');
                            $childCount = $child->products_count ?? 0;
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ $i * 50 }}ms;">
                            <article class="d-card d-ccard--tile">
                                <a href="{{ route('category', [$locale, $childSlug]) }}"
                                   class="d-card__link"
                                   aria-label="{{ $childName }}"></a>
                                <div class="d-ccard__media">
                                    <x-image-frame :media="$childImg" sizes="(max-width: 48rem) 50vw, 280px" :alt="$childName" contain />
                                </div>
                                <div class="d-ccard__body">
                                    <div style="flex:1;min-width:0;">
                                        <h3 class="d-ccard__name">{{ $childName }}</h3>
                                        <div class="d-ccard__count">
                                            {{ __('ui.n_products', ['count' => number_format($childCount)]) }}
                                        </div>
                                    </div>
                                    <span class="d-bcard__arrow" aria-hidden="true">
                                        <x-icon name="arrow-right" :size="16" />
                                    </span>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 3. TOP PICKS ========== --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-sec__head">
                    <div class="d-sec__head-text">
                        <x-eyebrow>{{ __('ui.cat_top_picks_eyebrow') }}</x-eyebrow>
                        <h2 class="d-sec__title">{{ __('ui.cat_top_picks_title') }}</h2>
                    </div>
                    <a href="{{ route('products', $locale) }}" class="d-btn d-btn--outline" style="flex:none;">
                        {{ __('ui.view_all') }}
                        <x-icon name="arrow-right" :size="16" />
                    </a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($featuredProducts as $i => $product)
                        <div class="d-rv" style="--rv-d:{{ $i * 60 }}ms;">
                            @include('site._product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 4. BUYING GUIDE + FAQ ========== --}}
    @if ($showGuide)
        <section class="d-sec">
            <div class="d-container">
                <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:40px;align-items:start;" class="d-cat-guide">
                    {{-- Left: eyebrow / title + brand chips --}}
                    <div style="display:grid;gap:20px;align-content:start;">
                        <div style="display:grid;gap:10px;">
                            <x-eyebrow>{{ __('ui.cat_buying_guide') }}</x-eyebrow>
                            @if ($guideTitle)
                                <h2 class="t-h2" style="margin:0;">{{ $guideTitle }}</h2>
                            @endif
                        </div>
                        @if ($brands->isNotEmpty())
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                @foreach ($brands as $brand)
                                    <a href="{{ route('brand.show', [$locale, $brand->slug]) }}"
                                       class="d-chip">{{ $brand->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    {{-- Right: FAQ accordion --}}
                    @if (count($faqItems) > 0)
                        <x-accordion
                            :items="$faqItems"
                            :default-open="[$faqItems[0]['id'] ?? '']"
                        />
                    @endif
                </div>
            </div>
        </section>
    @endif

    <x-site.quote-band />

</x-layouts.app>
