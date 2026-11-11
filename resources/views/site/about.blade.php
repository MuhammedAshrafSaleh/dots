@php
    use App\Models\PageSection;

    /** @var \Illuminate\Support\Collection<string,PageSection> $sections */
    $sec   = fn (string $key): ?PageSection => $sections->get($key);
    $field = fn (string $key, string $f): string => $sec($key)?->field($f, $locale) ?? '';
    $mediaObj = fn (string $key, string $f): ?\Spatie\MediaLibrary\MediaCollections\Models\Media => $sec($key)?->getMedia('section')
        ->first(fn ($m) => $m->getCustomProperty('field') === $f);
    $media = fn (string $key, string $f): ?string => $mediaObj($key, $f)?->getUrl();

    $heroImg   = $media('hero', 'image');
    $heroMedia = $mediaObj('hero', 'image');
    $heroVid   = $media('hero', 'video');

    $storyImgs = $sec('story')?->getMedia('section')
        ->filter(fn ($m) => $m->getCustomProperty('field') === 'images')
        ->values()
        ->all() ?? [];

    $statsItems = $sec('stats')?->content['items'] ?? [];

    $mvMissionImg = $mediaObj('mission_vision', 'mission_image');
    $mvVisionImg  = $mediaObj('mission_vision', 'vision_image');

    $discItems = $sec('disciplines')?->field('items', $locale) ?? [];
    // Media for each discipline card is stored as item_image_0/1/2
    $discImgs  = array_map(fn ($i) => $mediaObj('disciplines', 'item_image_'.$i), [0, 1, 2]);

    $whyItems = $sec('why')?->field('items', $locale) ?? [];

    $ctaBg = $mediaObj('cta', 'background_image');
@endphp
<x-layouts.app :title="$field('hero', 'title') ?: __('ui.about')">
<x-seo :title="$field('hero', 'title') ?: __('ui.about')" :description="$field('hero', 'lede')" />

    {{-- ========== 1. HERO ========== --}}
    <section class="d-hi" aria-label="{{ $field('hero', 'eyebrow') }}">
        <div class="d-hi__media">
            {{-- Poster image is the static background layer (paints immediately on
                 slow connections instead of a black block); the <video> plays on top. --}}
            <x-image-frame
                :src="$heroImg"
                :media="$heroMedia"
                sizes="100vw"
                :label="$field('hero', 'title')"
                eager
                priority
                style="position:absolute;inset:0;"
            />
            @if ($heroVid)
                <video
                    src="{{ $heroVid }}"
                    @if ($heroImg) poster="{{ $heroImg }}" @endif
                    autoplay muted loop playsinline
                    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                    aria-hidden="true"
                ></video>
            @endif
            <div style="position:absolute;inset:0;background:linear-gradient({{ $dir === 'rtl' ? '270deg' : '90deg' }},rgba(24,24,22,.8) 0%,rgba(24,24,22,.5) 45%,rgba(24,24,22,.08) 80%);"></div>
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

                @if ($field('hero', 'title'))
                    <h1 class="t-display d-hi__in" style="--hi-d:1850ms;margin:0;font-size:clamp(42px,5.6vw,82px);line-height:1.05;text-wrap:balance;">
                        {{ $field('hero', 'title') }}
                    </h1>
                @endif

                @if ($field('hero', 'text'))
                    <p class="t-body-lg d-hi__in" style="--hi-d:2050ms;margin:0;color:rgba(255,255,255,.9);max-width:580px;">
                        {{ $field('hero', 'text') }}
                    </p>
                @endif

                <div class="d-hi__in" style="--hi-d:2200ms;display:flex;gap:12px;flex-wrap:wrap;">
                    @if ($field('hero', 'button1_label'))
                        <x-button size="lg" icon-end="arrow-right"
                            :href="$field('hero', 'button1_link') ?: route('projects.previous')">
                            {{ $field('hero', 'button1_label') }}
                        </x-button>
                    @endif
                    @if ($field('hero', 'button2_label'))
                        <x-button size="lg"
                            style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.6);"
                            :href="$field('hero', 'button2_link') ?: '#'">
                            {{ $field('hero', 'button2_label') }}
                        </x-button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ========== 2. STORY ========== --}}
    @if ($field('story', 'title') || $field('story', 'text'))
        <section class="d-sec">
            <div class="d-container">
                <div class="d-about-story" style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;">
                    <div class="d-rv" style="display:grid;gap:18px;">
                        @if ($field('story', 'eyebrow'))
                            <x-eyebrow>{{ $field('story', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('story', 'title'))
                            <h2 class="t-h2" style="margin:0;">{{ $field('story', 'title') }}</h2>
                        @endif
                        @if ($field('story', 'text'))
                            <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $field('story', 'text') }}</p>
                        @endif
                    </div>
                    @if ($storyImgs)
                        <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:12px;">
                            <div class="d-rv">
                                <x-image-frame ratio="3 / 4" radius="var(--radius-lg)" :media="$storyImgs[0] ?? null" sizes="(max-width: 60rem) 50vw, 320px" :alt="$field('story', 'title')" />
                            </div>
                            <div style="display:grid;gap:12px;align-content:end;">
                                <div class="d-rv" style="--rv-d:80ms;">
                                    <x-image-frame ratio="1 / 1" radius="var(--radius-lg)" :media="$storyImgs[1] ?? null" sizes="(max-width: 60rem) 50vw, 320px" />
                                </div>
                                <div class="d-rv" style="--rv-d:160ms;">
                                    <x-image-frame ratio="4 / 3" radius="var(--radius-lg)" :media="$storyImgs[2] ?? null" sizes="(max-width: 60rem) 50vw, 320px" />
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 3. STATS ========== --}}
    @if ($statsItems)
        <section class="d-sec d-sec--tight" style="padding-top:0;">
            <div class="d-container">
                <div class="d-rv d-stats">
                    @foreach ($statsItems as $stat)
                        @php
                            $statValue  = is_array($stat['value'] ?? null)
                                ? ($stat['value'][$locale] ?? $stat['value']['en'] ?? '')
                                : ($stat['value'] ?? '');
                            $statSuffix = is_array($stat['suffix'] ?? null)
                                ? ($stat['suffix'][$locale] ?? $stat['suffix']['en'] ?? '')
                                : ($stat['suffix'] ?? '');
                            $statLabel  = is_array($stat['label'] ?? null)
                                ? ($stat['label'][$locale] ?? $stat['label']['en'] ?? '')
                                : ($stat['label'] ?? '');
                        @endphp
                        <div class="d-stat">
                            <div class="d-stat__n">
                                {{ $statValue }}
                                @if ($statSuffix)
                                    <sup>{{ $statSuffix }}</sup>
                                @endif
                            </div>
                            <div class="d-stat__l">{{ $statLabel }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 4. MISSION / VISION ========== --}}
    @if ($field('mission_vision', 'mission_text') || $field('mission_vision', 'vision_text'))
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-about-mv" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    {{-- Mission --}}
                    @if ($field('mission_vision', 'mission_text'))
                        <div class="d-rv" style="background:var(--white);border:1px solid var(--border-1);border-radius:var(--radius-xl);overflow:hidden;">
                            @if ($mvMissionImg)
                                <x-image-frame :media="$mvMissionImg" sizes="(max-width: 60rem) 100vw, 50vw" ratio="16 / 9" />
                            @endif
                            <div style="padding:32px;display:grid;gap:14px;">
                                <span style="width:48px;height:48px;border-radius:50%;background:var(--yellow-100);display:grid;place-items:center;">
                                    <x-icon name="target" :size="24" />
                                </span>
                                <h3 class="t-h3" style="margin:0;">{{ __('ui.about_mission') }}</h3>
                                <p class="t-body" style="margin:0;color:var(--fg-2);">{{ $field('mission_vision', 'mission_text') }}</p>
                            </div>
                        </div>
                    @endif
                    {{-- Vision --}}
                    @if ($field('mission_vision', 'vision_text'))
                        <div class="d-rv" style="--rv-d:80ms;background:var(--white);border:1px solid var(--border-1);border-radius:var(--radius-xl);overflow:hidden;">
                            @if ($mvVisionImg)
                                <x-image-frame :media="$mvVisionImg" sizes="(max-width: 60rem) 100vw, 50vw" ratio="16 / 9" />
                            @endif
                            <div style="padding:32px;display:grid;gap:14px;">
                                <span style="width:48px;height:48px;border-radius:50%;background:var(--yellow-100);display:grid;place-items:center;">
                                    <x-icon name="eye" :size="24" />
                                </span>
                                <h3 class="t-h3" style="margin:0;">{{ __('ui.about_vision') }}</h3>
                                <p class="t-body" style="margin:0;color:var(--fg-2);">{{ $field('mission_vision', 'vision_text') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 5. DISCIPLINES ========== --}}
    @if ($field('disciplines', 'eyebrow') || $discItems)
        <section class="d-sec">
            <div class="d-container">
                @if ($field('disciplines', 'eyebrow') || $field('disciplines', 'title'))
                    <div class="d-sec__head" style="margin-bottom:32px;">
                        <div class="d-sec__head-text">
                            @if ($field('disciplines', 'eyebrow'))
                                <x-eyebrow>{{ $field('disciplines', 'eyebrow') }}</x-eyebrow>
                            @endif
                            @if ($field('disciplines', 'title'))
                                <h2 class="d-sec__title">{{ $field('disciplines', 'title') }}</h2>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="d-about-discs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;">
                    @foreach ($discItems as $i => $item)
                        <div class="d-rv" style="--rv-d:{{ $i * 80 }}ms;">
                            <article class="d-card d-prcard d-prcard--overlay" style="min-height:480px;">
                                @if ($item['link'] ?? null)
                                    <a href="{{ $item['link'] }}" class="d-card__link" aria-label="{{ $item['title'] ?? '' }}"></a>
                                @endif
                                <div class="d-prcard__media">
                                    <x-image-frame :media="$discImgs[$i] ?? null" sizes="(max-width: 48rem) 100vw, 33vw" style="position:absolute;inset:0;height:100%;" />
                                </div>
                                <div class="d-card__body" style="padding:28px;gap:12px;">
                                    @if ($item['icon'] ?? null)
                                        <span style="width:48px;height:48px;border-radius:50%;background:var(--accent);color:var(--ink-900);display:grid;place-items:center;">
                                            <x-icon :name="$item['icon']" :size="24" />
                                        </span>
                                    @endif
                                    <h3 class="t-h3" style="margin:0;">{{ $item['title'] ?? '' }}</h3>
                                    @if ($item['text'] ?? null)
                                        <p style="margin:0;font:500 15px/1.55 var(--font-body);color:rgba(255,255,255,.9);">{{ $item['text'] }}</p>
                                    @endif
                                    <span class="d-scard__more" style="color:#fff;">
                                        {{ __('ui.explore') }}<x-icon name="arrow-right" :size="16" />
                                    </span>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 6. WHY DOTS ========== --}}
    @if ($whyItems)
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                @if ($field('why', 'eyebrow') || $field('why', 'title'))
                    <div class="d-sec__head">
                        <div class="d-sec__head-text">
                            @if ($field('why', 'eyebrow'))
                                <x-eyebrow>{{ $field('why', 'eyebrow') }}</x-eyebrow>
                            @endif
                            @if ($field('why', 'title'))
                                <h2 class="d-sec__title">{{ $field('why', 'title') }}</h2>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="d-about-why" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;border-top:1px solid var(--border-2);">
                    @foreach ($whyItems as $i => $item)
                        <div class="d-rv"
                             style="--rv-d:{{ ($i % 3) * 60 }}ms;padding:32px 28px 32px {{ $i % 3 ? '28px' : '0' }};{{ $i % 3 ? 'border-inline-start:1px solid var(--border-2);' : '' }}border-bottom:1px solid var(--border-2);display:grid;gap:12px;align-content:start;">
                            <span style="position:relative;width:48px;height:48px;border-radius:12px;border:1px solid var(--border-2);background:var(--white);display:grid;place-items:center;">
                                <x-icon :name="$item['icon'] ?? 'check'" :size="24" />
                                <span style="position:absolute;top:-4px;inset-inline-end:-4px;width:10px;height:10px;border-radius:50%;background:var(--accent);border:2px solid var(--ink-50);"></span>
                            </span>
                            <h3 class="t-h5" style="margin:0;">{{ $item['title'] ?? '' }}</h3>
                            <p class="t-body-sm" style="margin:0;color:var(--fg-2);">{{ $item['text'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 7. PARTNERS ========== --}}
    @if ($partners->isNotEmpty())
        <section class="d-sec">
            <div class="d-container">
                <div class="d-sec__head">
                    <div class="d-sec__head-text">
                        @if ($field('partners_head', 'eyebrow'))
                            <x-eyebrow>{{ $field('partners_head', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('partners_head', 'title'))
                            <h2 class="d-sec__title">{{ $field('partners_head', 'title') }}</h2>
                        @endif
                    </div>
                    <x-button variant="outline" icon-end="arrow-right" :href="route('brands')">
                        {{ $field('partners_head', 'button_label') ?: __('ui.all_brands') }}
                    </x-button>
                </div>

                <div class="d-about-partners d-rv" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($partners as $i => $brand)
                        <div class="d-rv" style="--rv-d:{{ ($i % 4) * 60 }}ms;">
                            <a href="{{ route('brands') }}" class="d-card d-bcard">
                                <div class="d-bcard__logo">
                                    @php $logoMedia = $brand->getFirstMedia('logo'); @endphp
                                    @if ($logoMedia)
                                        <img
                                            src="{{ \App\Support\ResponsiveImage::fallbackUrl($logoMedia) }}"
                                            @if ($logoSrcset = \App\Support\ResponsiveImage::srcset($logoMedia)) srcset="{{ $logoSrcset }}" sizes="160px" @endif
                                            alt="{{ $brand->name }}"
                                            loading="lazy"
                                            decoding="async"
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

    {{-- ========== 8. CTA BAND ========== --}}
    @if ($field('cta', 'title'))
        <section style="position:relative;overflow:hidden;">
            @if ($ctaBg)
                <x-image-frame :media="$ctaBg" sizes="100vw" style="position:absolute;inset:0;" />
            @endif
            <div style="position:absolute;inset:0;background:rgba(24,24,22,.72);"></div>
            <div class="d-about-cta d-container" style="position:relative;padding-block:104px;display:grid;gap:20px;justify-items:center;text-align:center;color:#fff;">
                <div class="d-rv" style="display:grid;gap:18px;justify-items:center;max-width:720px;">
                    <h2 class="t-h1" style="margin:0;">{{ $field('cta', 'title') }}</h2>
                    @if ($field('cta', 'text'))
                        <p class="t-body-lg" style="margin:0;color:rgba(255,255,255,.9);">{{ $field('cta', 'text') }}</p>
                    @endif
                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                        <x-whatsapp-button source="about_cta" class="d-btn--lg" />
                        <x-button size="lg" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.6);" :href="route('services')">
                            {{ __('ui.our_services') }}
                        </x-button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 9. QUOTE BAND ========== --}}
    <x-site.quote-band />

</x-layouts.app>
