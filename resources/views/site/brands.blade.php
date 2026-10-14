@php
    use App\Models\PageSection;

    /** @var \Illuminate\Support\Collection<string,PageSection> $sections */
    /** @var \App\Models\Brand|null $featured */
    /** @var \Illuminate\Support\Collection $brands */
    /** @var \Illuminate\Support\Collection $allLetters */
    /** @var string $q */
    /** @var string $letter */

    $sec   = fn (string $key): ?PageSection => $sections->get($key);
    $field = fn (string $key, string $f): string => $sec($key)?->field($f, $locale) ?? '';

    $filterUrl = fn (string $l, ?string $search = null) => request()->fullUrlWithQuery([
        'letter' => $l,
        'q'      => $search ?? $q,
    ]);
@endphp
<x-layouts.app :title="$field('head', 'title') ?: __('ui.brands')">

    {{-- ========== 1. PAGE HEAD ========== --}}
    <section class="d-sec d-sec--subtle">
        <div class="d-container">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'href' => route('home')],
                ['label' => __('ui.brands')],
            ]" />
            <div class="d-rv" style="display:grid;gap:16px;padding-top:32px;padding-bottom:24px;">
                <div style="display:grid;gap:12px;max-width:680px;">
                    @if ($field('head', 'eyebrow'))
                        <x-eyebrow>{{ $field('head', 'eyebrow') }}</x-eyebrow>
                    @endif
                    @if ($field('head', 'title'))
                        <h1 class="t-display" style="margin:0;">{{ $field('head', 'title') }}</h1>
                    @endif
                    @if ($field('head', 'lede'))
                        <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $field('head', 'lede') }}</p>
                    @endif
                </div>
                {{-- Search --}}
                <form method="GET" action="{{ route('brands', $locale) }}" style="margin-top:4px;">
                    @if ($letter && $letter !== 'all')
                        <input type="hidden" name="letter" value="{{ $letter }}">
                    @endif
                    <div style="position:relative;display:inline-flex;align-items:center;width:min(100%,360px);">
                        <span style="position:absolute;inset-inline-start:14px;color:var(--fg-3);pointer-events:none;display:flex;">
                            <x-icon name="search" :size="16" />
                        </span>
                        <input
                            class="d-input"
                            type="search"
                            name="q"
                            value="{{ $q }}"
                            placeholder="{{ __('ui.find_brand') }}"
                            style="padding-inline-start:40px;width:100%;"
                            aria-label="{{ __('ui.find_brand') }}"
                        />
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ========== 2. A–Z FILTER + FEATURED + GRID ========== --}}
    <section class="d-sec">
        <div class="d-container">

            {{-- A–Z letter bar (always LTR) --}}
            <div class="d-rv" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;" dir="ltr">
                <a href="{{ $filterUrl('all') }}"
                   class="d-chip {{ (! $letter || $letter === 'all') ? 'd-chip--active' : '' }}">
                    {{ __('ui.all_letters') }}
                </a>
                @foreach ($allLetters as $l)
                    <a href="{{ $filterUrl($l) }}"
                       class="d-chip {{ $letter === $l ? 'd-chip--active' : '' }}">
                        {{ $l }}
                    </a>
                @endforeach
            </div>

            {{-- Featured brand banner --}}
            @if ($featured && (! $q) && (! $letter || $letter === 'all'))
                @php
                    $featCover = $featured->getFirstMediaUrl('feature') ?: $featured->getFirstMediaUrl('cover');
                    $featText  = $featured->getTranslation('feature_text', $locale)
                                 ?: $featured->getTranslation('description', $locale);
                @endphp
                <div class="d-rv d-brands-feature" style="display:grid;grid-template-columns:1.2fr 1fr;border:1px solid var(--border-1);border-radius:var(--radius-xl);overflow:hidden;margin-bottom:32px;">
                    <x-image-frame
                        :src="$featCover ?: null"
                        :alt="$featured->name"
                        style="min-height:320px;"
                    />
                    <div class="d-brands-feature__body" style="padding:40px;display:grid;gap:14px;align-content:center;">
                        <x-badge tone="accent">{{ __('ui.brand_featured') }}</x-badge>
                        <h2 class="t-h3" style="margin:0;">{{ $featured->name }}</h2>
                        @if ($featText)
                            <p class="t-body" style="margin:0;color:var(--fg-2);">{{ $featText }}</p>
                        @endif
                        <a href="{{ route('brand.show', [$locale, $featured->slug]) }}"
                           class="d-btn d-btn--dark"
                           style="justify-self:start;">
                            {{ __('ui.explore_brand', ['name' => $featured->name]) }}
                            <x-icon name="arrow-right" :size="16" />
                        </a>
                    </div>
                </div>
            @endif

            {{-- Brand grid --}}
            @if ($brands->isNotEmpty())
                <div class="d-brands-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($brands as $i => $brand)
                        @php
                            $logoUrl = $brand->getFirstMediaUrl('logo');
                            $origin  = $brand->getTranslation('origin_country', $locale);
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ ($i % 4) * 60 }}ms;">
                            <article class="d-card d-bcard">
                                <a href="{{ route('brand.show', [$locale, $brand->slug]) }}"
                                   class="d-card__link"
                                   aria-label="{{ $brand->name }}"></a>
                                <div class="d-bcard__logo">
                                    @if ($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="{{ $brand->name }}"
                                             style="max-height:80px;max-width:160px;object-fit:contain;">
                                    @else
                                        <span class="d-bcard__word">{{ $brand->name }}</span>
                                    @endif
                                </div>
                                <div class="d-card__body" style="padding:16px 20px;">
                                    <div class="d-bcard__row">
                                        <div>
                                            <h3 class="d-bcard__name">{{ $brand->name }}</h3>
                                            @if ($origin)
                                                <span class="d-bcard__origin">{{ $origin }}</span>
                                            @endif
                                        </div>
                                        <span class="d-bcard__arrow" aria-hidden="true">
                                            <x-icon name="arrow-right" :size="14" />
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="t-body" style="color:var(--fg-2);text-align:center;padding:60px 0;">
                    {{ __('ui.no_brands_filter') }}
                </p>
            @endif

        </div>
    </section>

    <x-site.quote-band />

</x-layouts.app>
