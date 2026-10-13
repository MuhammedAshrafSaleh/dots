@php
    use App\Models\PageSection;

    /** @var \Illuminate\Support\Collection<string,PageSection> $sections */
    /** @var \Illuminate\Support\Collection $services */
    /** @var \Illuminate\Support\Collection $faqs */

    $sec   = fn (string $key): ?PageSection => $sections->get($key);
    $field = fn (string $key, string $f): string => $sec($key)?->field($f, $locale) ?? '';

    $processItems = $sec('process')?->field('items', $locale) ?? [];

    $faqItems = $faqs->map(fn ($faq) => [
        'id'      => 'faq-'.$faq->id,
        'title'   => $faq->getTranslation('question', $locale),
        'content' => $faq->getTranslation('answer', $locale),
    ])->all();
@endphp
<x-layouts.app :title="$field('head', 'title') ?: __('ui.services')">

    {{-- ========== 1. PAGE HEAD ========== --}}
    <section class="d-sec d-sec--subtle">
        <div class="d-container">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'href' => route('home')],
                ['label' => __('ui.services')],
            ]" />
            <div class="d-rv" style="max-width:680px;display:grid;gap:16px;padding-top:32px;">
                @if ($field('head', 'eyebrow'))
                    <x-eyebrow>{{ $field('head', 'eyebrow') }}</x-eyebrow>
                @endif
                @if ($field('head', 'title'))
                    <h1 class="t-display" style="margin:0;">{{ $field('head', 'title') }}</h1>
                @endif
                @if ($field('head', 'intro'))
                    <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $field('head', 'intro') }}</p>
                @endif
                @if ($field('head', 'button_label'))
                    <div>
                        <x-whatsapp-button source="services" :label="$field('head', 'button_label')" class="d-btn--lg" />
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ========== 2. SERVICE CARDS ========== --}}
    @if ($services->isNotEmpty())
        <section class="d-sec">
            <div class="d-container">
                <div class="d-rv d-services-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;">
                    @foreach ($services as $i => $service)
                        @php
                            $imgUrl = $service->getFirstMediaUrl('image');
                            $slug   = $service->getTranslation('slug', $locale);
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ ($i % 2) * 80 }}ms;">
                            <a href="{{ route('service.show', [$locale, $slug]) }}" class="d-card">
                                <div class="d-scard__media">
                                    <x-image-frame ratio="4 / 3" :src="$imgUrl ?: null" :alt="$service->getTranslation('title', $locale)" />
                                </div>
                                <div class="d-scard__body">
                                    <span class="d-scard__num">{{ $service->number }}</span>
                                    <h2 class="d-scard__title">{{ $service->getTranslation('title', $locale) }}</h2>
                                    <p class="d-scard__desc">{{ $service->getTranslation('excerpt', $locale) }}</p>
                                    <span class="d-scard__more">
                                        {{ __('ui.explore') }}
                                        <x-icon name="arrow-right" :size="16" />
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 3. PROCESS ========== --}}
    @if ($processItems)
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-sec__head">
                    @if ($field('process', 'eyebrow'))
                        <x-eyebrow>{{ $field('process', 'eyebrow') }}</x-eyebrow>
                    @endif
                    @if ($field('process', 'title'))
                        <h2 class="d-sec__title t-h2">{{ $field('process', 'title') }}</h2>
                    @endif
                </div>
                <div class="d-rv d-process" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;">
                    @foreach ($processItems as $i => $step)
                        <div class="d-rv d-process__step" style="--rv-d:{{ $i * 80 }}ms;display:grid;gap:12px;padding-inline-end:24px;position:relative;">
                            <div style="display:flex;align-items:center;gap:0;">
                                <span class="d-co__num" style="width:40px;height:40px;font-size:15px;">{{ $i + 1 }}</span>
                                @if ($i < count($processItems) - 1)
                                    <span class="d-process__line" style="flex:1;height:1.5px;background:var(--ink-200);margin-inline-start:12px;"></span>
                                @endif
                            </div>
                            @if ($step['title'] ?? null)
                                <h3 class="t-h4" style="margin:0;">{{ $step['title'] }}</h3>
                            @endif
                            @if ($step['text'] ?? null)
                                <p class="t-body-sm" style="margin:0;color:var(--fg-2);">{{ $step['text'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 4. FAQ ========== --}}
    @if ($faqItems)
        <section class="d-sec">
            <div class="d-container">
                <div class="d-rv d-services-faq" style="display:grid;grid-template-columns:1fr 1.4fr;gap:40px;align-items:start;">
                    <div style="display:grid;gap:12px;">
                        @if ($field('faq_head', 'eyebrow'))
                            <x-eyebrow>{{ $field('faq_head', 'eyebrow') }}</x-eyebrow>
                        @endif
                        @if ($field('faq_head', 'title'))
                            <h2 class="t-h2" style="margin:0;">{{ $field('faq_head', 'title') }}</h2>
                        @endif
                    </div>
                    <x-accordion :items="$faqItems" :multiple="true" :default-open="[$faqItems[0]['id'] ?? '']" />
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 5. QUOTE BAND ========== --}}
    <x-site.quote-band />

</x-layouts.app>
