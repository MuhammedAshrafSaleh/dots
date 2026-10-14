@php
    use App\Models\PageSection;

    /** @var \Illuminate\Support\Collection<string,PageSection> $sections */
    $sec = fn (string $key): ?PageSection => $sections->get($key);
    $field = fn (string $key, string $f): string => $sec($key)?->field($f, $locale) ?? '';
    $media = fn (string $key, string $f): ?string => $sec($key)?->getMedia('section')
        ->first(fn ($m) => $m->getCustomProperty('field') === $f)?->getUrl();

    $heroVid    = $media('hero', 'video');
    $heroPoster = $media('hero', 'poster');
    $heroWords  = array_values(array_filter(explode(' ', $field('hero', 'headline'))));

    $smartImg = $media('smart_banner', 'image');
@endphp
<x-layouts.app :title="$field('hero', 'headline') ?: 'DOTS'">
<x-seo :title="$field('hero', 'headline')" :description="$field('hero', 'subhead')" :image="$heroPoster" />

    {{-- ========== 1. HERO ========== --}}
    <section
        x-data="{ run: 0, paused: false }"
        :key="run"
        class="d-hi"
        aria-label="{{ $field('hero', 'eyebrow') }}"
    >
        {{-- media layer --}}
        <div class="d-hi__media">
            <x-image-frame
                :src="$heroPoster"
                :video="$heroVid"
                :label="$field('hero', 'eyebrow')"
                style="position:absolute;inset:0;"
                x-bind:style="paused ? 'display:block' : ''"
            />
            @if ($heroVid)
                <video
                    x-ref="vid"
                    src="{{ $heroVid }}"
                    @if ($heroPoster) poster="{{ $heroPoster }}" @endif
                    autoplay muted loop playsinline
                    x-show="!paused"
                    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                    aria-hidden="true"
                ></video>
            @endif
            <div style="position:absolute;inset:0;background:linear-gradient({{ $dir === 'rtl' ? '270deg' : '90deg' }},rgba(24,24,22,.8) 0%,rgba(24,24,22,.5) 42%,rgba(24,24,22,.05) 78%);"></div>
            <div style="position:absolute;inset:0;background:linear-gradient(0deg,rgba(24,24,22,.45),transparent 45%);"></div>
            <div class="d-hi__glow"></div>
        </div>
        <div class="d-hi__bulb" aria-hidden="true"><span></span></div>

        <div class="d-container" style="position:relative;width:100%;padding-block:96px;">
            <div style="max-width:760px;display:grid;gap:24px;color:var(--white);">
                @if ($field('hero', 'eyebrow'))
                    <span class="d-hi__in" style="--hi-d:1700ms;display:inline-flex;align-items:center;gap:10px;font:800 12px/1 var(--font-body);letter-spacing:var(--tracking-overline);text-transform:var(--text-transform-overline);">
                        <span class="d-dot"></span>{{ $field('hero', 'eyebrow') }}
                    </span>
                @endif

                @if ($heroWords)
                    <h1 class="t-display d-hi__title" style="margin:0;font-size:clamp(48px,6.4vw,92px);line-height:1.02;text-wrap:balance;">
                        @foreach ($heroWords as $i => $word)
                            <span class="d-hi__w"><span style="--hi-d:{{ 1850 + $i * 70 }}ms;">{{ $word }}</span>@if (!$loop->last) &nbsp;@endif</span>
                        @endforeach
                    </h1>
                @endif

                @if ($field('hero', 'subheadline'))
                    <p class="t-body-lg d-hi__in" style="--hi-d:{{ 1850 + count($heroWords) * 70 + 150 }}ms;margin:0;color:rgba(255,255,255,.9);max-width:560px;">
                        {{ $field('hero', 'subheadline') }}
                    </p>
                @endif

                <div class="d-hi__in" style="--hi-d:{{ 1850 + count($heroWords) * 70 + 300 }}ms;display:flex;gap:12px;flex-wrap:wrap;">
                    @if ($field('hero', 'button1_label'))
                        <x-button size="lg" icon-end="arrow-right" :href="$field('hero', 'button1_link') ?: route('products')">
                            {{ $field('hero', 'button1_label') }}
                        </x-button>
                    @endif
                    @if ($field('hero', 'button2_label'))
                        <x-button size="lg" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.6);" :href="$field('hero', 'button2_link') ?: route('services')">
                            {{ $field('hero', 'button2_label') }}
                        </x-button>
                    @endif
                </div>
            </div>

            {{-- Replay / pause controls --}}
            <div class="d-hi__in" style="--hi-d:2600ms;position:absolute;inset-inline-end:var(--page-margin);bottom:96px;display:flex;gap:8px;">
                <x-icon-button
                    icon="rotate-ccw"
                    :label="__('ui.replay_intro')"
                    @click="run++"
                    style="background:rgba(255,255,255,.14);color:#fff;"
                />
                <x-icon-button
                    :icon="'pause'"
                    :label="__('ui.pause_video')"
                    x-bind:icon="paused ? 'play' : 'pause'"
                    x-bind:aria-label="paused ? '{{ __('ui.play_video') }}' : '{{ __('ui.pause_video') }}'"
                    @click="paused = !paused"
                    style="background:rgba(255,255,255,.14);color:#fff;"
                />
            </div>
        </div>
    </section>

    {{-- ========== 2. FEATURES STRIP ========== --}}
    @php $features = $sec('features'); $featureItems = $features?->field('items', $locale) ?? []; @endphp
    @if ($featureItems)
        <div class="d-features">
            <div class="d-container d-features__grid">
                @foreach ($featureItems as $item)
                    <div class="d-features__item">
                        <span class="d-features__icon">
                            <x-icon :name="$item['icon'] ?? 'check'" :size="20" />
                        </span>
                        {{ $item['text'] ?? '' }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========== 3. SHOP BY CATEGORY ========== --}}
    @if ($categories->isNotEmpty())
        <section class="d-sec">
            <div class="d-container">
                <div class="d-sec__head">
                    <div class="d-sec__head-text">
                        @if ($field('categories_head', 'eyebrow'))
                            <x-eyebrow>{{ $field('categories_head', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('categories_head', 'title'))
                            <h2 class="d-sec__title">{{ $field('categories_head', 'title') }}</h2>
                        @endif
                    </div>
                    <x-button variant="outline" icon-end="arrow-right" :href="route('products')">
                        {{ $field('categories_head', 'button_label') ?: __('ui.all_categories') }}
                    </x-button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;">
                    @foreach ($categories as $i => $cat)
                        @php $catSlug = $cat->getTranslation('slug', $locale); @endphp
                        <div class="d-rv" style="--rv-d:{{ ($i % 3) * 60 }}ms;">
                            <a href="{{ route('category', ['category' => $catSlug]) }}" class="d-card d-ccard">
                                <div class="d-ccard__media">
                                    <x-image-frame
                                        :src="$cat->getFirstMediaUrl('image')"
                                        :alt="$cat->getTranslation('name', $locale)"
                                    />
                                </div>
                                <div class="d-ccard__body">
                                    <div>
                                        <h3 class="d-ccard__name">{{ $cat->getTranslation('name', $locale) }}</h3>
                                        <p class="d-ccard__count">{{ trans('ui.n_products', ['count' => $cat->products_count]) }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 4. FEATURED PRODUCTS (tabs) ========== --}}
    @if ($newProducts->isNotEmpty() || $bestProducts->isNotEmpty() || $smartProducts->isNotEmpty())
        <section class="d-sec d-sec--subtle" x-data="{ tab: 'new' }">
            <div class="d-container">
                <div class="d-sec__head">
                    <div class="d-sec__head-text">
                        @if ($field('featured_head', 'eyebrow'))
                            <x-eyebrow>{{ $field('featured_head', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('featured_head', 'title'))
                            <h2 class="d-sec__title">{{ $field('featured_head', 'title') }}</h2>
                        @endif
                    </div>
                    <x-tabs
                        variant="pill"
                        :tabs="[
                            ['id' => 'new',  'label' => $field('featured_head', 'tab_new_label')  ?: __('ui.tab_new')],
                            ['id' => 'best', 'label' => $field('featured_head', 'tab_best_label') ?: __('ui.tab_best')],
                            ['id' => 'smart','label' => $field('featured_head', 'tab_smart_label')?: __('ui.tab_smart')],
                        ]"
                        value="new"
                        @tab-changed.window="tab = $event.detail"
                    />
                </div>

                {{-- New arrivals --}}
                <div x-show="tab === 'new'" x-cloak style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($newProducts as $product)
                        @include('site._product-card', ['product' => $product])
                    @endforeach
                </div>
                {{-- Best sellers --}}
                <div x-show="tab === 'best'" x-cloak style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($bestProducts as $product)
                        @include('site._product-card', ['product' => $product])
                    @endforeach
                </div>
                {{-- Smart home --}}
                <div x-show="tab === 'smart'" x-cloak style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($smartProducts as $product)
                        @include('site._product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 5. SERVICES ========== --}}
    @if ($services->isNotEmpty())
        <section class="d-sec">
            <div class="d-container">
                <div class="d-sec__head">
                    <div class="d-sec__head-text">
                        @if ($field('services_head', 'eyebrow'))
                            <x-eyebrow>{{ $field('services_head', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('services_head', 'title'))
                            <h2 class="d-sec__title">{{ $field('services_head', 'title') }}</h2>
                        @endif
                    </div>
                    <x-button variant="dark" icon-end="arrow-right" :href="route('services')">
                        {{ $field('services_head', 'button_label') ?: __('ui.all_services') }}
                    </x-button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($services as $i => $service)
                        @php $serviceSlug = $service->getTranslation('slug', $locale); @endphp
                        <div class="d-rv" style="--rv-d:{{ $i * 60 }}ms;">
                            <a href="{{ route('services') }}" class="d-card d-scard">
                                <div class="d-scard__media">
                                    <x-image-frame
                                        :src="$service->getFirstMediaUrl('image')"
                                        :alt="$service->getTranslation('title', $locale)"
                                    />
                                </div>
                                <div class="d-scard__body">
                                    @if ($service->number)
                                        <span class="d-scard__num">{{ $service->number }}</span>
                                    @endif
                                    <h3 class="d-scard__title">{{ $service->getTranslation('title', $locale) }}</h3>
                                    @if ($service->getTranslation('excerpt', $locale))
                                        <p class="d-scard__desc">{{ $service->getTranslation('excerpt', $locale) }}</p>
                                    @endif
                                    <span class="d-scard__more">
                                        {{ __('ui.view_all') }}
                                        <x-icon name="arrow-right" :size="14" />
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 6. SMART HOME BANNER ========== --}}
    @php $smartBanner = $sec('smart_banner'); @endphp
    @if ($smartBanner && ($field('smart_banner', 'title') || $smartImg))
        <section class="d-sec d-sec--subtle d-sec--tight">
            <div class="d-container">
                <div class="d-rv d-smart">
                    <div class="d-smart__text">
                        @if ($field('smart_banner', 'eyebrow'))
                            <x-eyebrow>{{ $field('smart_banner', 'eyebrow') }}</x-eyebrow>
                        @endif
                        <h2 class="t-h2" style="margin:0;">{{ $field('smart_banner', 'title') }}</h2>
                        @if ($field('smart_banner', 'text'))
                            <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $field('smart_banner', 'text') }}</p>
                        @endif
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            @if ($field('smart_banner', 'button1_label'))
                                <x-button variant="dark" :href="$field('smart_banner', 'button1_link') ?: route('services')">
                                    {{ $field('smart_banner', 'button1_label') }}
                                </x-button>
                            @endif
                            @if ($field('smart_banner', 'button2_label'))
                                <x-button variant="ghost" icon-end="arrow-right" :href="$field('smart_banner', 'button2_link') ?: route('products')">
                                    {{ $field('smart_banner', 'button2_label') }}
                                </x-button>
                            @endif
                        </div>
                    </div>
                    <div class="d-smart__media">
                        <x-image-frame
                            :src="$smartImg"
                            :label="$field('smart_banner', 'eyebrow')"
                            style="height:100%;"
                        />
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 7. BRANDS ========== --}}
    @if ($brands->isNotEmpty())
        <section class="d-sec">
            <div class="d-container">
                <div class="d-sec__head">
                    <div class="d-sec__head-text">
                        @if ($field('brands_head', 'eyebrow'))
                            <x-eyebrow>{{ $field('brands_head', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('brands_head', 'title'))
                            <h2 class="d-sec__title">{{ $field('brands_head', 'title') }}</h2>
                        @endif
                    </div>
                    <x-button variant="outline" icon-end="arrow-right" :href="route('brands')">
                        {{ $field('brands_head', 'button_label') ?: __('ui.all_brands') }}
                    </x-button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($brands as $i => $brand)
                        <div class="d-rv" style="--rv-d:{{ ($i % 4) * 60 }}ms;">
                            <a href="{{ route('brands') }}" class="d-card d-bcard">
                                <div class="d-bcard__logo">
                                    @if ($brand->getFirstMediaUrl('logo'))
                                        <img
                                            src="{{ $brand->getFirstMediaUrl('logo') }}"
                                            alt="{{ $brand->name }}"
                                            loading="lazy"
                                            style="max-height:80px;max-width:100%;object-fit:contain;"
                                        >
                                    @else
                                        <span class="d-bcard__word">{{ $brand->name }}</span>
                                    @endif
                                </div>
                                <div class="d-card__body">
                                    <div class="d-bcard__row">
                                        <div>
                                            <h3 class="d-bcard__name">{{ $brand->name }}</h3>
                                            @if ($brand->getTranslation('origin_country', $locale))
                                                <p class="d-bcard__origin">{{ $brand->getTranslation('origin_country', $locale) }}</p>
                                            @endif
                                        </div>
                                        <span class="d-bcard__arrow" aria-hidden="true">
                                            <x-icon name="arrow-right" :size="16" />
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 8. QUOTE BAND ========== --}}
    <x-site.quote-band />

</x-layouts.app>
