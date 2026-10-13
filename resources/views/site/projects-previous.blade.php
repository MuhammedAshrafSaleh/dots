@php
    use App\Enums\ProjectType;
    use App\Models\PageSection;

    /** @var \Illuminate\Pagination\LengthAwarePaginator $projects */
    /** @var \Illuminate\Support\Collection $years */
    /** @var string $type */
    /** @var string $year */
    /** @var ProjectType[] $types */
    /** @var PageSection|null $section */

    $filterUrl = fn (array $extra) => request()->fullUrlWithQuery(array_merge(['type' => $type, 'year' => $year, 'page' => 1], $extra));

    $heroImg   = $section?->getMedia('section')->where('custom_properties.field', 'image')->first()?->getUrl();
    $eyebrow   = $section?->field('eyebrow', $locale) ?: __('ui.portfolio');
    $title     = $section?->field('title', $locale) ?: __('ui.previous_projects');
    $lede      = $section?->field('lede', $locale) ?: __('ui.previous_projects_sub');
@endphp
<x-layouts.app :title="$title">

    {{-- ========== 1. HERO ========== --}}
    @if ($heroImg)
        <section class="d-media-hero" style="min-height:400px;position:relative;display:flex;flex-direction:column;justify-content:flex-end;background:var(--ink-900);overflow:hidden;">
            <img src="{{ $heroImg }}" alt="" aria-hidden="true"
                 style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.45;">
            <div style="position:absolute;inset:0;background:linear-gradient(0deg,rgba(24,24,22,.75) 0%,rgba(24,24,22,.2) 60%,transparent 100%);"></div>
            <div class="d-container" style="position:relative;z-index:2;padding-block:40px;">
                <x-breadcrumbs :items="[
                    ['label' => __('ui.home'), 'href' => route('home')],
                    ['label' => __('ui.projects'), 'href' => route('projects.current')],
                    ['label' => __('ui.previous_projects')],
                ]" :dark="true" />
                <div class="d-rv" style="max-width:680px;display:grid;gap:14px;padding-top:28px;color:var(--white);">
                    <x-eyebrow style="color:rgba(255,255,255,.75);">{{ $eyebrow }}</x-eyebrow>
                    <h1 class="t-display" style="margin:0;color:var(--white);">{{ $title }}</h1>
                    @if ($lede)
                        <p class="t-body-lg" style="margin:0;color:rgba(255,255,255,.8);">{{ $lede }}</p>
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <x-breadcrumbs :items="[
                    ['label' => __('ui.home'), 'href' => route('home')],
                    ['label' => __('ui.projects'), 'href' => route('projects.current')],
                    ['label' => __('ui.previous_projects')],
                ]" />
                <div class="d-rv" style="max-width:680px;display:grid;gap:14px;padding-top:32px;padding-bottom:40px;">
                    <x-eyebrow>{{ $eyebrow }}</x-eyebrow>
                    <h1 class="t-display" style="margin:0;">{{ $title }}</h1>
                    @if ($lede)
                        <p class="t-body-lg" style="margin:0;color:var(--fg-2);">{{ $lede }}</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ========== 2. FILTERS + MASONRY GRID ========== --}}
    <section class="d-sec">
        <div class="d-container">

            {{-- Filter row: type chips + year links --}}
            <div class="d-rv" style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:32px;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ $filterUrl(['type' => 'all']) }}"
                       class="d-chip {{ $type === 'all' ? 'd-chip--active' : '' }}">
                        {{ __('ui.all_types') }}
                    </a>
                    @foreach ($types as $t)
                        <a href="{{ $filterUrl(['type' => $t->value]) }}"
                           class="d-chip {{ $type === $t->value ? 'd-chip--active' : '' }}">
                            {{ $t->label() }}
                        </a>
                    @endforeach
                </div>

                {{-- Year filter --}}
                @if ($years->isNotEmpty())
                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                        <a href="{{ $filterUrl(['year' => 'all']) }}"
                           class="d-chip {{ $year === 'all' ? 'd-chip--active' : '' }}" style="height:28px;font-size:12px;">
                            {{ __('ui.all_years') }}
                        </a>
                        @foreach ($years as $y)
                            <a href="{{ $filterUrl(['year' => $y]) }}"
                               class="d-chip {{ (string) $year === (string) $y ? 'd-chip--active' : '' }}" style="height:28px;font-size:12px;">
                                {{ $y }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Masonry-style 3-col grid (CSS columns) --}}
            @if ($projects->isNotEmpty())
                <div class="d-rv d-prev-masonry" style="columns:3;column-gap:20px;">
                    @foreach ($projects as $i => $project)
                        @php
                            $pImg  = $project->getFirstMediaUrl('cover');
                            $pSlug = $project->getTranslation('slug', $locale);
                        @endphp
                        <div class="d-rv" style="break-inside:avoid;margin-bottom:20px;--rv-d:{{ ($i % 3) * 60 }}ms;">
                            <a href="{{ route('project.show', [$locale, $pSlug]) }}"
                               class="d-card d-prcard--overlay d-card--linked" style="display:flex;flex-direction:column;min-height:300px;text-decoration:none;">
                                @if ($pImg)
                                    <div class="d-prcard__media" style="position:absolute;inset:0;">
                                        <img src="{{ $pImg }}" alt="{{ $project->getTranslation('title', $locale) }}"
                                             style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                @endif
                                <div style="position:absolute;inset:0;background:linear-gradient(0deg,rgba(24,24,22,.82) 0%,rgba(24,24,22,.2) 50%,transparent 70%);pointer-events:none;z-index:1;"></div>
                                <div class="d-card__body d-prcard--overlay" style="position:relative;z-index:2;margin-top:auto;padding:20px;color:var(--white);">
                                    <div class="d-prcard__badges" style="position:static;margin-bottom:10px;">
                                        <x-badge tone="success" :dot="true">{{ __('ui.completed_badge') }} {{ $project->completed_year }}</x-badge>
                                        <x-badge tone="outline">{{ $project->type->label() }}</x-badge>
                                    </div>
                                    <h3 class="d-prcard__title" style="color:var(--white);">
                                        {{ $project->getTranslation('title', $locale) }}
                                    </h3>
                                    @if ($project->getTranslation('location', $locale))
                                        <span class="d-prcard__loc" style="color:rgba(255,255,255,.85);margin-top:8px;display:block;">
                                            <x-icon name="map-pin" :size="13" />
                                            {{ $project->getTranslation('location', $locale) }}
                                        </span>
                                    @endif
                                    @if ($project->getTranslation('scope_summary', $locale))
                                        <div class="d-prcard__foot" style="margin-top:10px;border-color:rgba(255,255,255,.2);color:rgba(255,255,255,.7);">
                                            {{ $project->getTranslation('scope_summary', $locale) }}
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div style="margin-top:40px;padding-top:16px;border-top:1px solid var(--border-1);">
                    <x-pagination
                        :page="$projects->currentPage()"
                        :total="$projects->lastPage()"
                        :count="$projects->total()"
                        :per-page="$projects->perPage()"
                    />
                </div>
            @else
                <p class="t-body" style="color:var(--fg-2);text-align:center;padding:60px 0;">
                    {{ __('ui.no_projects_filter') }}
                </p>
            @endif

        </div>
    </section>

    {{-- ========== 3. QUOTE BAND ========== --}}
    <x-site.quote-band />

</x-layouts.app>
