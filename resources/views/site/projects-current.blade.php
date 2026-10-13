@php
    use App\Enums\ProjectStage;
    use App\Enums\ProjectType;

    /** @var \Illuminate\Support\Collection $projects */
    /** @var \App\Models\Project|null $lead */
    /** @var string $type */
    /** @var ProjectType[] $types */

    $typeUrl = fn (string $t) => request()->fullUrlWithQuery(['type' => $t]);

    $stages = ProjectStage::cases();
    $stageLabels = array_map(fn ($s) => $s->label(), $stages);
@endphp
<x-layouts.app :title="__('ui.current_projects')">

    {{-- ========== 1. HERO ========== --}}
    @php
        $heroImg = $lead?->getFirstMediaUrl('cover') ?? '';
    @endphp
    <section class="d-media-hero" style="min-height:480px;position:relative;display:flex;flex-direction:column;justify-content:flex-end;background:var(--ink-900);overflow:hidden;">
        @if ($heroImg)
            <img src="{{ $heroImg }}" alt="" aria-hidden="true"
                 style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.45;">
        @endif
        <div style="position:absolute;inset:0;background:linear-gradient(0deg,rgba(24,24,22,.75) 0%,rgba(24,24,22,.2) 60%,transparent 100%);"></div>
        <div class="d-container" style="position:relative;z-index:2;padding-block:40px;">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'href' => route('home')],
                ['label' => __('ui.projects')],
            ]" :dark="true" />
            <div class="d-rv" style="max-width:680px;display:grid;gap:14px;padding-top:28px;color:var(--white);">
                <x-eyebrow style="color:rgba(255,255,255,.75);">{{ __('ui.current_projects') }}</x-eyebrow>
                <h1 class="t-display" style="margin:0;color:var(--white);">{{ __('ui.current_projects_sub') }}</h1>
                <x-whatsapp-button source="projects_current" class="d-btn--lg d-btn--whatsapp" style="width:fit-content;">
                    {{ __('ui.request_quote') }}
                </x-whatsapp-button>
            </div>
        </div>
    </section>

    {{-- ========== 2. TYPE FILTER + GRID ========== --}}
    <section class="d-sec">
        <div class="d-container">

            {{-- Filter chips --}}
            <div class="d-rv" style="display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:32px;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ $typeUrl('all') }}"
                       class="d-chip {{ $type === 'all' ? 'd-chip--active' : '' }}">
                        {{ __('ui.all_types') }}
                    </a>
                    @foreach ($types as $t)
                        <a href="{{ $typeUrl($t->value) }}"
                           class="d-chip {{ $type === $t->value ? 'd-chip--active' : '' }}">
                            {{ $t->label() }}
                        </a>
                    @endforeach
                </div>
                @if ($projects->isNotEmpty())
                    <span style="font:700 13px var(--font-body);color:var(--fg-2);display:inline-flex;gap:8px;align-items:center;">
                        <span class="d-dot" style="animation:d-pulse-node 2.4s infinite;"></span>
                        {{ $projects->count() }} {{ __('ui.active_sites') }}
                    </span>
                @endif
            </div>

            {{-- Featured lead card (type=all only) --}}
            @if ($lead && $type === 'all')
                @php
                    $leadImg  = $lead->getFirstMediaUrl('cover');
                    $leadSlug = $lead->getTranslation('slug', $locale);
                    $stageIdx = $lead->stage?->index() ?? 0;
                    $leadProgress = $lead->progress;
                @endphp
                <div class="d-rv" style="margin-bottom:24px;">
                    <a href="{{ route('project.show', [$locale, $leadSlug]) }}" class="d-card d-prcard-lead"
                       style="display:grid;grid-template-columns:1.4fr 1fr;text-decoration:none;">
                        <div class="d-prcard__media d-prcard-lead__media" style="aspect-ratio:auto;min-height:380px;">
                            <x-image-frame ratio="auto" style="height:100%;" :src="$leadImg ?: null"
                                           :alt="$lead->getTranslation('title', $locale)" />
                        </div>
                        <div class="d-card__body d-prcard-lead__body" style="padding:36px;display:grid;gap:16px;align-content:center;">
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <x-badge tone="accent" :dot="true">{{ __('ui.in_progress') }}</x-badge>
                                <x-badge tone="outline">{{ $lead->type->label() }}</x-badge>
                            </div>
                            <h2 class="t-h2" style="margin:0;">{{ $lead->getTranslation('title', $locale) }}</h2>
                            @if ($lead->getTranslation('location', $locale))
                                <span class="d-prcard__loc">
                                    <x-icon name="map-pin" :size="14" />
                                    {{ $lead->getTranslation('location', $locale) }}
                                </span>
                            @endif

                            {{-- Progress bar --}}
                            <div class="d-progress">
                                <div class="d-progress__head">
                                    <span>{{ $lead->getTranslation('phase_label', $locale) }}</span>
                                    <b>{{ $leadProgress }}%</b>
                                </div>
                                <div class="d-progress__track">
                                    <div class="d-progress__fill" style="width:{{ $leadProgress }}%;"></div>
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

                            <span class="d-scard__more">
                                {{ __('ui.follow_project') }}
                                <x-icon name="arrow-right" :size="16" />
                            </span>
                        </div>
                    </a>
                </div>
            @endif

            {{-- Project cards grid --}}
            @php
                $gridProjects = ($type === 'all' && $lead) ? $projects->filter(fn ($p) => $p->id !== $lead->id) : $projects;
            @endphp
            @if ($gridProjects->isNotEmpty())
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;" class="d-projects-grid">
                    @foreach ($gridProjects as $i => $project)
                        @php
                            $pImg  = $project->getFirstMediaUrl('cover');
                            $pSlug = $project->getTranslation('slug', $locale);
                            $pStageIdx = $project->stage?->index() ?? 0;
                        @endphp
                        <div class="d-rv" style="--rv-d:{{ ($i % 3) * 60 }}ms;">
                            <a href="{{ route('project.show', [$locale, $pSlug]) }}" class="d-card d-card--linked" style="text-decoration:none;">
                                <div class="d-prcard__media" style="position:relative;">
                                    <x-image-frame ratio="4 / 3" :src="$pImg ?: null" :alt="$project->getTranslation('title', $locale)" />
                                    <div class="d-prcard__badges">
                                        <x-badge tone="accent" :dot="true">{{ __('ui.in_progress') }}</x-badge>
                                        <x-badge tone="outline">{{ $project->type->label() }}</x-badge>
                                    </div>
                                </div>
                                <div class="d-card__body" style="padding:20px;display:grid;gap:12px;">
                                    <h3 class="d-prcard__title">{{ $project->getTranslation('title', $locale) }}</h3>
                                    @if ($project->getTranslation('location', $locale))
                                        <span class="d-prcard__loc">
                                            <x-icon name="map-pin" :size="13" />
                                            {{ $project->getTranslation('location', $locale) }}
                                        </span>
                                    @endif
                                    <div class="d-progress">
                                        <div class="d-progress__head">
                                            <span>{{ $project->getTranslation('phase_label', $locale) }}</span>
                                            <b>{{ $project->progress }}%</b>
                                        </div>
                                        <div class="d-progress__track">
                                            <div class="d-progress__fill" style="width:{{ $project->progress }}%;"></div>
                                        </div>
                                    </div>
                                    <div class="d-prcard__foot">
                                        <span>{{ $project->start_date?->format('M Y') }}</span>
                                        <span class="d-scard__more" style="font-size:12px;">
                                            {{ __('ui.follow_project') }}
                                            <x-icon name="arrow-right" :size="14" />
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @elseif ($projects->isEmpty())
                <p class="t-body" style="color:var(--fg-2);text-align:center;padding:60px 0;">
                    {{ __('ui.no_projects_filter') }}
                </p>
            @endif

        </div>
    </section>

    {{-- ========== 3. QUOTE BAND ========== --}}
    <x-site.quote-band />

</x-layouts.app>
