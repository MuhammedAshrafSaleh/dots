@php
    use App\Models\PageSection;

    /** @var \App\Models\Service $service */
    /** @var \Illuminate\Support\Collection<string,PageSection> $sections */

    $sec   = fn (string $key): ?PageSection => $sections->get($key);

    $processItems = $sec('process')?->field('items', $locale) ?? [];

    $heroImg    = $service->getFirstMediaUrl('image');
    $galleryImg = $service->getMedia('gallery')->map(fn ($m) => $m->getUrl())->values();

    $serviceItems = $service->items;

    $faqItems = $service->faqs->map(fn ($faq) => [
        'id'      => 'faq-'.$faq->id,
        'title'   => $faq->getTranslation('question', $locale),
        'content' => $faq->getTranslation('answer', $locale),
    ])->all();
@endphp
<x-layouts.app :title="$service->getTranslation('meta_title', $locale) ?: $service->getTranslation('title', $locale)">
<x-seo
    :title="$service->getTranslation('meta_title', $locale) ?: $service->getTranslation('title', $locale)"
    :description="$service->getTranslation('meta_description', $locale) ?: $service->getTranslation('excerpt', $locale)"
    :image="$heroImg"
    :alternate-url="$alternateUrl ?? null"
/>

    {{-- ========== 1. HEADER ========== --}}
    <div style="border-bottom:1px solid var(--border-1);">
        <div class="d-container" style="padding-block:28px;">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'),     'href' => route('home')],
                ['label' => __('ui.services'), 'href' => route('services')],
                ['label' => $service->getTranslation('title', $locale)],
            ]" />
            <div class="d-rv d-svc-head" style="display:grid;grid-template-columns:1fr 1.1fr;gap:56px;align-items:center;padding-top:32px;padding-bottom:40px;">
                <div style="display:grid;gap:18px;">
                    <span class="d-scard__num">{{ $service->number }} · {{ __('ui.service_label') }}</span>
                    <h1 class="t-display" style="margin:0;">{{ $service->getTranslation('title', $locale) }}</h1>
                    <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $service->getTranslation('description', $locale) }}</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <x-button size="lg" icon-end="arrow-right" href="#k-book">
                            {{ __('ui.book_consultation') }}
                        </x-button>
                        <x-whatsapp-button source="service_detail" :model="$service" class="d-btn--lg" />
                    </div>
                </div>
                @if ($heroImg)
                    <div class="d-rv" style="--rv-d:80ms;">
                        <x-image-frame ratio="4 / 3" radius="var(--radius-xl)" :src="$heroImg" :alt="$service->getTranslation('title', $locale)" />
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========== 2. WHAT'S INCLUDED ========== --}}
    @if ($serviceItems->isNotEmpty() || $galleryImg->isNotEmpty())
        <section class="d-sec">
            <div class="d-container">
                <div class="d-rv d-svc-inc" style="display:grid;grid-template-columns:1fr 1fr;gap:40px;">
                    <div>
                        <div style="display:grid;gap:12px;margin-bottom:20px;">
                            <x-eyebrow>{{ __('ui.whats_included') }}</x-eyebrow>
                            @if ($service->getTranslation('included_title', $locale))
                                <h2 class="t-h2" style="margin:0;">{{ $service->getTranslation('included_title', $locale) }}</h2>
                            @endif
                            @if ($service->getTranslation('duration_text', $locale))
                                <p class="t-body-sm" style="margin:0;color:var(--fg-3);">{{ $service->getTranslation('duration_text', $locale) }}</p>
                            @endif
                        </div>
                        @if ($serviceItems->isNotEmpty())
                            <ul class="d-node-list t-body" style="color:var(--fg-1);font-weight:600;">
                                @foreach ($serviceItems as $item)
                                    <li>{{ $item->getTranslation('text', $locale) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @if ($galleryImg->isNotEmpty())
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <x-image-frame ratio="3 / 4" radius="var(--radius-lg)" :src="$galleryImg->get(0)" />
                            <div style="display:grid;gap:12px;">
                                <x-image-frame ratio="4 / 3" radius="var(--radius-lg)" :src="$galleryImg->get(1)" />
                                <x-image-frame ratio="4 / 3" radius="var(--radius-lg)" :src="$galleryImg->get(2)" />
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 3. PROCESS ========== --}}
    @if ($processItems)
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-sec__head">
                    <x-eyebrow>{{ __('ui.process_eyebrow') }}</x-eyebrow>
                    <h2 class="d-sec__title t-h2">{{ $service->getTranslation('duration_text', $locale) }}</h2>
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

    {{-- ========== 4. CONSULTATION FORM + FAQ ========== --}}
    <section class="d-sec">
        <div class="d-container">
            <div class="d-rv d-svc-book" style="display:grid;grid-template-columns:1.3fr 1fr;gap:32px;align-items:start;">
                <livewire:site.consultation-form :service-id="$service->id" />
                @if ($faqItems)
                    <x-accordion :items="$faqItems" :multiple="true" :default-open="[$faqItems[1]['id'] ?? ($faqItems[0]['id'] ?? '')]" />
                @endif
            </div>
        </div>
    </section>

</x-layouts.app>
