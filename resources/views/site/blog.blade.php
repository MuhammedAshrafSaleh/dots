@php
    use App\Models\PageSection;

    /** @var \Illuminate\Support\Collection<string,PageSection> $sections */
    /** @var \Illuminate\Support\Collection<string,PageSection> $globalSections */
    /** @var \Illuminate\Support\Collection $categories */
    /** @var \App\Models\Post|null $featured */
    /** @var \Illuminate\Pagination\LengthAwarePaginator $posts */
    /** @var string $q */
    /** @var string $cat */

    $sec   = fn (string $key): ?PageSection => $sections->get($key);
    $field = fn (string $key, string $f): string => $sec($key)?->field($f, $locale) ?? '';

    $gsec   = fn (string $key): ?PageSection => $globalSections->get($key);
    $gfield = fn (string $key, string $f): string => $gsec($key)?->field($f, $locale) ?? '';

    $catUrl = fn (string $c, ?string $search = null) => request()->fullUrlWithQuery([
        'cat' => $c,
        'page' => 1,
        'q' => $search ?? $q,
    ]);
@endphp
<x-layouts.app :title="$field('head', 'title') ?: __('ui.blog')">

    {{-- ========== 1. PAGE HEAD ========== --}}
    <section class="d-sec d-sec--subtle">
        <div class="d-container">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'href' => route('home')],
                ['label' => __('ui.blog')],
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
                <form method="GET" action="{{ route('blog', $locale) }}" style="margin-top:4px;">
                    @if ($cat && $cat !== 'all')
                        <input type="hidden" name="cat" value="{{ $cat }}">
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
                            placeholder="{{ __('ui.search_articles') }}"
                            style="padding-inline-start:40px;width:100%;"
                            aria-label="{{ __('ui.search_articles') }}"
                        />
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ========== 2. FEATURED + GRID ========== --}}
    <section class="d-sec">
        <div class="d-container">

            {{-- Featured wide card --}}
            @if ($featured)
                @php
                    $featImg  = $featured->getFirstMediaUrl('cover');
                    $featSlug = $featured->getTranslation('slug', $locale);
                    $featCat  = $featured->category?->getTranslation('name', $locale);
                    $featDate = $featured->published_at?->translatedFormat('j M Y');
                @endphp
                <div class="d-rv" style="margin-bottom:40px;">
                    <a href="{{ route('blog.show', [$locale, $featSlug]) }}" class="d-card d-acard d-acard--wide"
                       style="display:grid;grid-template-columns:1.2fr 1fr;text-decoration:none;">
                        <x-image-frame class="d-acard__media" ratio="4 / 3" :src="$featImg ?: null"
                                       :alt="$featured->getTranslation('title', $locale)" />
                        <div class="d-card__body" style="padding:36px;display:grid;gap:10px;align-content:center;">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <x-badge tone="accent">{{ __('ui.featured') }}</x-badge>
                                @if ($featCat)
                                    <x-badge tone="outline">{{ $featCat }}</x-badge>
                                @endif
                            </div>
                            <h3 class="d-acard__title">{{ $featured->getTranslation('title', $locale) }}</h3>
                            <p class="d-acard__ex">{{ $featured->getTranslation('excerpt', $locale) }}</p>
                            <div class="d-acard__meta" style="margin-top:auto;padding-top:6px;">
                                @if ($featured->getTranslation('author_name', $locale))
                                    <span style="color:var(--fg-1);font-weight:800;">{{ $featured->getTranslation('author_name', $locale) }}</span>
                                    <i></i>
                                @endif
                                <span>{{ $featDate }}</span>
                                <i></i>
                                <span style="display:inline-flex;align-items:center;gap:4px;">
                                    <x-icon name="clock" :size="13" />
                                    {{ $featured->read_minutes }} {{ __('ui.min_read') }}
                                </span>
                            </div>
                            <span class="d-scard__more" style="margin-top:6px;">
                                {{ __('ui.read_article') }}
                                <x-icon name="arrow-right" :size="16" />
                            </span>
                        </div>
                    </a>
                </div>
            @endif

            {{-- Category chips --}}
            <div class="d-rv" style="display:flex;gap:8px;flex-wrap:nowrap;overflow-x:auto;margin-bottom:28px;padding-bottom:2px;">
                <a href="{{ $catUrl('all') }}"
                   class="d-chip {{ (! $cat || $cat === 'all') ? 'd-chip--active' : '' }}"
                   style="flex:none;">
                    {{ __('ui.all_categories_blog') }}
                </a>
                @foreach ($categories as $category)
                    @php
                        $catSlug = $category->getTranslation('slug', $locale);
                    @endphp
                    <a href="{{ $catUrl($catSlug) }}"
                       class="d-chip {{ $cat === $catSlug ? 'd-chip--active' : '' }}"
                       style="flex:none;">
                        {{ $category->getTranslation('name', $locale) }}
                    </a>
                @endforeach
            </div>

            {{-- Article grid --}}
            @if ($posts->isNotEmpty())
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;" class="d-blog-grid">
                    @foreach ($posts as $i => $post)
                        @php
                            $pImg  = $post->getFirstMediaUrl('cover');
                            $pSlug = $post->getTranslation('slug', $locale);
                            $pCat  = $post->category?->getTranslation('name', $locale);
                            $pDate = $post->published_at?->translatedFormat('j M Y');
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ ($i % 3) * 60 }}ms;">
                            <article class="d-card d-acard">
                                <a href="{{ route('blog.show', [$locale, $pSlug]) }}"
                                   class="d-card__link" aria-label="{{ $post->getTranslation('title', $locale) }}"></a>
                                <x-image-frame class="d-acard__media" ratio="4 / 3"
                                               :src="$pImg ?: null"
                                               :alt="$post->getTranslation('title', $locale)" />
                                @if ($pCat)
                                    <span class="d-acard__tag">
                                        <x-badge tone="outline">{{ $pCat }}</x-badge>
                                    </span>
                                @endif
                                <div class="d-card__body" style="gap:10px;padding:20px;">
                                    <h3 class="d-acard__title">{{ $post->getTranslation('title', $locale) }}</h3>
                                    <p class="d-acard__ex">{{ $post->getTranslation('excerpt', $locale) }}</p>
                                    <div class="d-acard__meta" style="margin-top:auto;padding-top:6px;">
                                        <span>{{ $pDate }}</span>
                                        <i></i>
                                        <span style="display:inline-flex;align-items:center;gap:4px;">
                                            <x-icon name="clock" :size="13" />
                                            {{ $post->read_minutes }} {{ __('ui.min_read') }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="t-body" style="color:var(--fg-2);text-align:center;padding:60px 0;">
                    {{ __('ui.no_articles_filter') }}
                </p>
            @endif

            {{-- Pagination --}}
            @if ($posts->hasPages())
                <div style="margin-top:40px;padding-top:16px;border-top:1px solid var(--border-1);">
                    <x-pagination
                        :page="$posts->currentPage()"
                        :total="$posts->lastPage()"
                        :count="$posts->total()"
                        :per-page="$posts->perPage()"
                    />
                </div>
            @endif

        </div>
    </section>

    {{-- ========== 3. NEWSLETTER BAND ========== --}}
    <section class="d-sec d-sec--subtle">
        <div class="d-container">
            <div class="d-rv d-nl-band" style="display:grid;grid-template-columns:1.2fr 1fr;gap:24px;align-items:center;">
                <div style="display:grid;gap:10px;">
                    @if ($gfield('newsletter', 'eyebrow'))
                        <x-eyebrow>{{ $gfield('newsletter', 'eyebrow') }}</x-eyebrow>
                    @endif
                    @if ($gfield('newsletter', 'title'))
                        <h2 class="t-h3" style="margin:0;">{{ $gfield('newsletter', 'title') }}</h2>
                    @endif
                </div>
                <livewire:site.newsletter-form source="blog" />
            </div>
        </div>
    </section>

</x-layouts.app>
