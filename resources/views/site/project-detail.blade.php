@php
    use App\Enums\ProjectStage;
    use App\Enums\ProjectStatus;

    /** @var \App\Models\Project $project */
    /** @var bool $isCurrent */
    /** @var \Illuminate\Support\Collection $related */

    $coverImg    = $project->getFirstMediaUrl('cover');
    $galleryImgs = $project->getMedia('gallery')->map(fn ($m) => $m->getUrl())->values();

    $stages    = ProjectStage::cases();
    $stageIdx  = $project->stage?->index() ?? 0;

    $facts = collect();
    if ($project->getTranslation('location', $locale)) {
        $facts->push(['map-pin', __('ui.location_label'), $project->getTranslation('location', $locale)]);
    }
    $facts->push(['building-2', __('ui.project_type_project'), $project->type->label()]);
    if ($isCurrent) {
        if ($project->start_date) {
            $facts->push(['calendar-days', __('ui.start_date_label'), $project->start_date->format('M Y')]);
        }
        if ($project->expected_completion) {
            $facts->push(['flag', __('ui.expected_completion_label'), $project->expected_completion->format('M Y')]);
        }
    } else {
        if ($project->completed_year) {
            $facts->push(['calendar-check', __('ui.completed_date_label'), (string) $project->completed_year]);
        }
        $clientVal = $project->getTranslation('client', $locale) ?: __('ui.private_client');
        $facts->push(['user', __('ui.client_label'), $clientVal]);
    }
@endphp
<x-layouts.app :title="$project->getTranslation('meta_title', $locale) ?: $project->getTranslation('title', $locale)">
<x-seo
    :title="$project->getTranslation('meta_title', $locale) ?: $project->getTranslation('title', $locale)"
    :description="$project->getTranslation('meta_description', $locale) ?: $project->getTranslation('scope_summary', $locale)"
    :image="$coverImg"
    :alternate-url="$alternateUrl ?? null"
/>

    {{-- ========== 1. BREADCRUMB + TITLE ========== --}}
    <div class="d-container" style="padding-top:24px;">
        <x-breadcrumbs :items="[
            ['label' => __('ui.home'), 'href' => route('home')],
            $isCurrent
                ? ['label' => __('ui.current_projects'), 'href' => route('projects.current')]
                : ['label' => __('ui.previous_projects'), 'href' => route('projects.previous')],
            ['label' => $project->getTranslation('title', $locale)],
        ]" />

        <div class="d-rv d-project-title-row" style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;padding-top:22px;padding-bottom:28px;">
            <div style="display:grid;gap:14px;max-width:760px;">
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    @if ($isCurrent)
                        <x-badge tone="accent" :dot="true">{{ __('ui.in_progress') }}</x-badge>
                    @else
                        <x-badge tone="success" :dot="true">{{ __('ui.completed_badge') }} {{ $project->completed_year }}</x-badge>
                    @endif
                    <x-badge tone="outline">{{ $project->type->label() }}</x-badge>
                </div>
                <h1 class="t-display" style="margin:0;">{{ $project->getTranslation('title', $locale) }}</h1>
            </div>

            {{-- Share bar --}}
            <div class="d-project-share" style="display:flex;gap:8px;align-items:center;" x-data="{ copied: false }">
                <button type="button"
                        class="d-btn d-btn--outline d-btn--sm"
                        x-on:click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)"
                        x-text="copied ? '{{ __('ui.link_copied') }}' : '{{ __('ui.copy_link') }}'">
                    {{ __('ui.copy_link') }}
                </button>
                <x-whatsapp-button source="project_share" :model="$project" class="d-btn--sm" />
            </div>
        </div>
    </div>

    {{-- ========== 2. GALLERY ========== --}}
    @if ($galleryImgs->isNotEmpty() || $coverImg)
        @php
            $allImgs = $galleryImgs->isNotEmpty() ? $galleryImgs : collect([$coverImg]);
        @endphp
        <div class="d-container" style="padding-bottom:28px;">
            @if ($allImgs->count() === 1)
                <x-image-frame ratio="21 / 9" radius="var(--radius-xl)" :src="$allImgs->first()" :alt="$project->getTranslation('title', $locale)" />
            @else
                <div class="d-project-gallery" style="display:grid;grid-template-columns:2fr 1fr;gap:8px;border-radius:var(--radius-xl);overflow:hidden;">
                    <div>
                        <x-image-frame ratio="4 / 3" :src="$allImgs->get(0)" :alt="$project->getTranslation('title', $locale)" />
                    </div>
                    <div style="display:grid;gap:8px;">
                        @foreach ($allImgs->skip(1)->take(2) as $img)
                            <x-image-frame ratio="4 / 3" :src="$img" alt="" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ========== 3. MAIN CONTENT + SIDEBAR ========== --}}
    <div class="d-container d-project-body" style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:56px;padding-bottom:72px;align-items:start;">

        {{-- LEFT: Content --}}
        <div style="display:grid;gap:40px;">

            @if ($isCurrent)
                {{-- Current project: about + scope --}}
                @php $about = $project->getTranslation('about', $locale); @endphp
                @if ($about)
                    <div class="d-rv d-prose" style="max-width:none;">
                        <h2 style="margin-top:0;">{{ __('ui.about_project') }}</h2>
                        {!! $about !!}
                    </div>
                @endif

                @php $scope = $project->getTranslation('scope_summary', $locale); @endphp
                @if ($scope)
                    <div class="d-rv" style="border-inline-start:3px solid var(--accent);padding-inline-start:18px;">
                        <h3 class="t-h4" style="margin:0 0 8px;">{{ __('ui.scope_of_work') }}</h3>
                        <p class="t-body" style="margin:0;color:var(--fg-2);">{{ $scope }}</p>
                    </div>
                @endif

            @else
                {{-- Completed project: challenge / solution / result cards --}}
                @php
                    $csrItems = array_filter([
                        ['circle-alert', __('ui.challenge'), $project->getTranslation('challenge', $locale), 0],
                        ['lightbulb',    __('ui.solution'), $project->getTranslation('solution', $locale), 1],
                        ['trending-down',__('ui.result'),   $project->getTranslation('result', $locale), 2],
                    ], fn ($row) => ! empty($row[2]));
                @endphp
                @if ($csrItems)
                    <div class="d-rv" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                        @foreach ($csrItems as [$ic, $heading, $desc, $idx])
                            <div class="d-rv" style="--rv-d:{{ $idx * 80 }}ms;border:1px solid var(--border-1);border-radius:var(--radius-lg);padding:22px;display:grid;gap:10px;align-content:start;background:{{ $idx === 2 ? 'var(--yellow-50)' : 'var(--white)' }};">
                                <span style="width:40px;height:40px;border-radius:50%;background:{{ $idx === 2 ? 'var(--accent)' : 'var(--ink-50)' }};display:grid;place-items:center;">
                                    <x-icon :name="$ic" :size="20" />
                                </span>
                                <h3 class="t-h4" style="margin:0;">{{ $heading }}</h3>
                                <p class="t-body-sm" style="margin:0;color:var(--fg-2);">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- About body --}}
                @php $about = $project->getTranslation('about', $locale); @endphp
                @if ($about)
                    <div class="d-rv d-prose" style="max-width:none;">
                        {!! $about !!}
                    </div>
                @endif
            @endif

            {{-- Products & services used --}}
            @if ($project->services->isNotEmpty() || $project->products->isNotEmpty())
                <div class="d-rv" style="display:grid;gap:16px;">
                    <h2 class="t-h3" style="margin:0;">{{ __('ui.products_services_used') }}</h2>
                    @if ($project->services->isNotEmpty())
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            @foreach ($project->services as $svc)
                                <a href="{{ route('service.show', [$locale, $svc->getTranslation('slug', $locale)]) }}"
                                   class="d-chip" style="text-decoration:none;">
                                    <x-icon name="check" :size="14" />
                                    {{ $svc->getTranslation('title', $locale) }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if ($project->products->isNotEmpty())
                        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;" class="d-proj-products">
                            @foreach ($project->products->take(4) as $prod)
                                @include('site._product-card', ['product' => $prod])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- RIGHT: Sidebar --}}
        <aside class="d-project-sidebar" style="display:grid;gap:16px;position:sticky;top:24px;">
            <div style="border:1px solid var(--border-1);border-radius:var(--radius-lg);padding:22px;display:grid;gap:18px;background:var(--white);">

                {{-- Progress bar --}}
                @if ($isCurrent)
                    <div class="d-progress">
                        <div class="d-progress__head">
                            <span>{{ $project->getTranslation('phase_label', $locale) }}</span>
                            <b>{{ $project->progress }}%</b>
                        </div>
                        <div class="d-progress__track">
                            <div class="d-progress__fill" style="width:{{ $project->progress }}%;"></div>
                        </div>
                        <div class="d-progress__steps">
                            @foreach ($stages as $i => $stage)
                                @php
                                    $cls = $i < $stageIdx ? 'd-progress__step--done'
                                         : ($i === $stageIdx ? 'd-progress__step--now' : '');
                                @endphp
                                <span class="d-progress__step {{ $cls }}">{{ $stage->label() }}</span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="d-progress d-progress--done">
                        <div class="d-progress__head">
                            <span>{{ __('ui.delivered') }}</span>
                            <b>100%</b>
                        </div>
                        <div class="d-progress__track">
                            <div class="d-progress__fill" style="width:100%;"></div>
                        </div>
                    </div>
                @endif

                {{-- Facts list --}}
                @if ($facts->isNotEmpty())
                    <div style="display:grid;gap:14px;">
                        @foreach ($facts as [$ic, $key, $val])
                            <div style="display:flex;gap:12px;align-items:center;">
                                <span style="width:36px;height:36px;border-radius:50%;background:var(--ink-50);display:grid;place-items:center;flex:none;">
                                    <x-icon :name="$ic" :size="18" />
                                </span>
                                <div>
                                    <div style="font:600 12px var(--font-body);color:var(--fg-3);">{{ $key }}</div>
                                    <div style="font:800 14px/1.3 var(--font-body);">{{ $val }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Scope summary divider line --}}
                @if ($project->getTranslation('scope_summary', $locale) && ! $isCurrent)
                    <div style="font:600 13px/1.5 var(--font-body);color:var(--fg-2);padding-top:14px;border-top:1px solid var(--border-1);">
                        {{ $project->getTranslation('scope_summary', $locale) }}
                    </div>
                @endif
            </div>

            <x-whatsapp-button source="project_detail" :model="$project" class="d-btn--lg d-btn--block">
                {{ __('ui.quote_similar_project') }}
            </x-whatsapp-button>
        </aside>

    </div>

    {{-- ========== 4. RELATED PROJECTS ========== --}}
    @if ($related->isNotEmpty())
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-sec__head">
                    <div>
                        <x-eyebrow>{{ __('ui.more_work') }}</x-eyebrow>
                        <h2 class="d-sec__title t-h2">
                            {{ $isCurrent ? __('ui.other_current_projects') : __('ui.related_projects') }}
                        </h2>
                    </div>
                    <a href="{{ $isCurrent ? route('projects.current') : route('projects.previous') }}"
                       class="d-btn d-btn--outline" style="display:inline-flex;align-items:center;gap:8px;">
                        {{ __('ui.all_projects') }}
                        <x-icon name="arrow-right" :size="16" />
                    </a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;" class="d-related-grid">
                    @foreach ($related as $i => $rel)
                        @php
                            $rImg  = $rel->getFirstMediaUrl('cover');
                            $rSlug = $rel->getTranslation('slug', $locale);
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ $i * 60 }}ms;">
                            <a href="{{ route('project.show', [$locale, $rSlug]) }}"
                               class="d-card d-card--linked" style="text-decoration:none;">
                                <div class="d-prcard__media" style="position:relative;">
                                    <x-image-frame ratio="4 / 3" :src="$rImg ?: null" :alt="$rel->getTranslation('title', $locale)" />
                                    <div class="d-prcard__badges">
                                        @if ($rel->status === \App\Enums\ProjectStatus::Current)
                                            <x-badge tone="accent" :dot="true">{{ __('ui.in_progress') }}</x-badge>
                                        @else
                                            <x-badge tone="success" :dot="true">{{ __('ui.completed_badge') }} {{ $rel->completed_year }}</x-badge>
                                        @endif
                                        <x-badge tone="outline">{{ $rel->type->label() }}</x-badge>
                                    </div>
                                </div>
                                <div class="d-card__body" style="padding:20px;display:grid;gap:10px;">
                                    <h3 class="d-prcard__title">{{ $rel->getTranslation('title', $locale) }}</h3>
                                    @if ($rel->getTranslation('location', $locale))
                                        <span class="d-prcard__loc">
                                            <x-icon name="map-pin" :size="13" />
                                            {{ $rel->getTranslation('location', $locale) }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 5. QUOTE BAND ========== --}}
    <x-site.quote-band />

</x-layouts.app>
