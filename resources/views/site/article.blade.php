@php
    /** @var \App\Models\Post $post */
    /** @var \Illuminate\Support\Collection $related */

    $catName  = $post->category?->getTranslation('name', $locale);
    $catSlug  = $post->category?->getTranslation('slug', $locale);
    $title    = $post->getTranslation('title', $locale);
    $excerpt  = $post->getTranslation('excerpt', $locale);
    $body     = $post->getTranslation('body', $locale);
    $author   = $post->getTranslation('author_name', $locale) ?: __('ui.dots_editorial_team');
    $role     = $post->getTranslation('author_role', $locale);
    $date     = $post->published_at?->translatedFormat('j M Y');
    $coverImg = $post->getFirstMediaUrl('cover');
    $authorImg = $post->getFirstMediaUrl('author');

    $crumbs = [
        ['label' => __('ui.home'), 'href' => route('home')],
        ['label' => __('ui.blog'), 'href' => route('blog', $locale)],
    ];
    if ($catName) {
        $crumbs[] = ['label' => $catName, 'href' => route('blog', $locale).'?cat='.$catSlug];
    }
@endphp
<x-layouts.app :title="$post->getTranslation('meta_title', $locale) ?: $title">

    {{-- ========== 1. ARTICLE HEADER ========== --}}
    <div class="d-container" style="padding-top:32px;display:grid;gap:24px;max-width:1000px;">
        <x-breadcrumbs :items="$crumbs" />

        <div class="d-rv" style="display:grid;gap:18px;">
            @if ($catName)
                <div style="display:flex;gap:8px;">
                    <x-badge tone="accent">{{ $catName }}</x-badge>
                </div>
            @endif

            <h1 class="t-h1" style="margin:0;text-wrap:balance;">{{ $title }}</h1>

            @if ($excerpt)
                <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $excerpt }}</p>
            @endif

            {{-- Author + meta + share bar --}}
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;justify-content:space-between;padding-block:14px;border-block:1px solid var(--border-1);">
                <div style="display:flex;align-items:center;gap:12px;">
                    @if ($authorImg)
                        <img src="{{ $authorImg }}" alt="{{ $author }}"
                             style="width:44px;height:44px;border-radius:50%;object-fit:cover;flex:none;" />
                    @else
                        <span style="width:44px;height:44px;border-radius:50%;background:var(--ink-100);display:grid;place-items:center;flex:none;">
                            <x-icon name="user" :size="20" />
                        </span>
                    @endif
                    <div>
                        <div style="font:800 14px/1.3 var(--font-body);">{{ $author }}</div>
                        @if ($role)
                            <div class="d-acard__meta" style="margin-top:2px;color:var(--fg-2);">{{ $role }}</div>
                        @endif
                        <div class="d-acard__meta" style="margin-top:4px;">
                            @if ($date)
                                <span>{{ $date }}</span>
                                <i></i>
                            @endif
                            <span style="display:inline-flex;align-items:center;gap:4px;">
                                <x-icon name="clock" :size="13" />
                                {{ $post->read_minutes }} {{ __('ui.min_read') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Share bar --}}
                <div x-data="{ copied: false }" style="display:flex;align-items:center;gap:8px;">
                    <span class="t-body-sm" style="color:var(--fg-2);">{{ __('ui.share') }}</span>
                    <button type="button" class="d-icon-btn"
                            aria-label="{{ __('ui.copy_link') }}"
                            x-on:click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)">
                        <x-icon name="link" :size="16" />
                    </button>
                    <span x-show="copied" x-cloak class="t-body-sm" style="color:var(--fg-2);">{{ __('ui.link_copied') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== 2. COVER IMAGE ========== --}}
    @if ($coverImg)
        <div class="d-container" style="padding-block:36px;max-width:1240px;">
            <x-image-frame ratio="21 / 9" radius="var(--radius-xl)" :src="$coverImg" :alt="$title" />
        </div>
    @endif

    {{-- ========== 3. BODY + SIDEBAR ========== --}}
    <div class="d-container d-article-layout" style="display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:56px;max-width:1060px;padding-bottom:80px;">
        <article class="d-prose">
            {!! $body !!}

            {{-- Tags --}}
            @if ($post->tags->isNotEmpty())
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:24px;">
                    @foreach ($post->tags as $tag)
                        <x-chip>{{ $tag->getTranslation('name', $locale) }}</x-chip>
                    @endforeach
                </div>
            @endif
        </article>

        <aside class="d-article-aside" style="display:grid;gap:20px;align-content:start;position:sticky;top:24px;">
            {{-- Products in this article --}}
            @if ($post->products->isNotEmpty())
                <div style="border:1px solid var(--border-1);border-radius:var(--radius-lg);overflow:hidden;">
                    @php $firstProd = $post->products->first(); @endphp
                    <x-image-frame ratio="4 / 3" :src="$firstProd?->getFirstMediaUrl('gallery') ?: null"
                                   :alt="$firstProd?->getTranslation('name', $locale) ?? ''" />
                    <div style="padding:16px;display:grid;gap:10px;">
                        <b style="font:600 17px/1.3 var(--font-display);">{{ __('ui.shop_products_article') }}</b>
                        <x-button size="sm" icon-end="arrow-right" href="{{ route('products', $locale) }}">
                            {{ __('ui.view_n_products', ['count' => $post->products->count()]) }}
                        </x-button>
                    </div>
                </div>
            @endif

            <x-whatsapp-button source="article" class="d-btn--block">
                {{ __('ui.ask_lighting_designer') }}
            </x-whatsapp-button>
        </aside>
    </div>

    {{-- ========== 4. RELATED ARTICLES ========== --}}
    @if ($related->isNotEmpty())
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-sec__head">
                    <x-eyebrow>{{ __('ui.keep_reading') }}</x-eyebrow>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                        <h2 class="d-sec__title t-h2">{{ __('ui.related_articles') }}</h2>
                        <x-button variant="outline" icon-end="arrow-right" href="{{ route('blog', $locale) }}">
                            {{ __('ui.all_articles') }}
                        </x-button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;" class="d-blog-grid">
                    @foreach ($related as $i => $rel)
                        @php
                            $relImg  = $rel->getFirstMediaUrl('cover');
                            $relSlug = $rel->getTranslation('slug', $locale);
                            $relCat  = $rel->category?->getTranslation('name', $locale);
                            $relDate = $rel->published_at?->translatedFormat('j M Y');
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ $i * 60 }}ms;">
                            <article class="d-card d-acard">
                                <a href="{{ route('blog.show', [$locale, $relSlug]) }}"
                                   class="d-card__link" aria-label="{{ $rel->getTranslation('title', $locale) }}"></a>
                                <x-image-frame class="d-acard__media" ratio="4 / 3"
                                               :src="$relImg ?: null"
                                               :alt="$rel->getTranslation('title', $locale)" />
                                @if ($relCat)
                                    <span class="d-acard__tag">
                                        <x-badge tone="outline">{{ $relCat }}</x-badge>
                                    </span>
                                @endif
                                <div class="d-card__body" style="gap:10px;padding:20px;">
                                    <h3 class="d-acard__title">{{ $rel->getTranslation('title', $locale) }}</h3>
                                    <p class="d-acard__ex">{{ $rel->getTranslation('excerpt', $locale) }}</p>
                                    <div class="d-acard__meta" style="margin-top:auto;padding-top:6px;">
                                        <span>{{ $relDate }}</span>
                                        <i></i>
                                        <span style="display:inline-flex;align-items:center;gap:4px;">
                                            <x-icon name="clock" :size="13" />
                                            {{ $rel->read_minutes }} {{ __('ui.min_read') }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.app>
