@php
    /** @var \App\Models\Brand $brand */
    /** @var \Illuminate\Support\Collection $tabs */
    /** @var string $catFilter */
    /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
    /** @var \Illuminate\Support\Collection $projects */

    $logoUrl  = $brand->getFirstMediaUrl('logo');
    $coverUrl = $brand->getFirstMediaUrl('cover');
    $pdfUrl   = $brand->getFirstMediaUrl('catalogue');
    $origin   = $brand->getTranslation('origin_country', $locale);
    $desc     = $brand->getTranslation('description', $locale);
    $catLabel = $brand->getTranslation('catalogue_label', $locale);

    $tabUrl = fn (string $id) => request()->fullUrlWithQuery(['cat' => $id, 'page' => 1]);
@endphp
<x-layouts.app :title="$brand->getTranslation('meta_title', $locale) ?: $brand->name">
<x-seo
    :title="$brand->getTranslation('meta_title', $locale) ?: $brand->name"
    :description="$brand->getTranslation('meta_description', $locale)"
    :image="$coverUrl ?: $logoUrl"
/>

    {{-- ========== 1. BRAND HEADER ========== --}}
    <div style="background:var(--bg-subtle);border-bottom:1px solid var(--border-1);">
        <div class="d-container" style="padding-block:28px;display:grid;gap:24px;">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'href' => route('home')],
                ['label' => __('ui.brands'), 'href' => route('brands', $locale)],
                ['label' => $brand->name],
            ]" />
            <div class="d-rv d-brand-header" style="display:grid;grid-template-columns:220px 1fr;gap:40px;align-items:center;">
                {{-- Logo / wordmark --}}
                <div class="d-brand-logo-box" style="aspect-ratio:1/1;background:var(--white);border:1px solid var(--border-1);border-radius:var(--radius-lg);display:grid;place-items:center;padding:24px;">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brand->name }}"
                             style="max-height:140px;max-width:100%;object-fit:contain;">
                    @else
                        <span class="d-bcard__word" style="font-size:30px;">{{ $brand->name }}</span>
                    @endif
                </div>
                {{-- Meta --}}
                <div style="display:grid;gap:12px;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @if ($brand->is_authorised)
                            <x-badge tone="dark">{{ __('ui.brand_authorised') }}</x-badge>
                        @endif
                        @if ($origin)
                            <x-badge tone="neutral">{{ $origin }}</x-badge>
                        @endif
                    </div>
                    <h1 class="t-h1" style="margin:0;">{{ $brand->name }}</h1>
                    @if ($desc)
                        <p class="t-body" style="margin:0;color:var(--fg-2);">{{ $desc }}</p>
                    @endif
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;">
                        @if ($pdfUrl)
                            <a href="{{ $pdfUrl }}" class="d-btn d-btn--outline" target="_blank" rel="noopener">
                                <x-icon name="download" :size="16" />
                                {{ $catLabel ?: __('ui.brand_catalogue_pdf', ['year' => now()->year]) }}
                            </a>
                        @endif
                        <x-whatsapp-button source="brand_detail" :model="$brand" class="d-btn--whatsapp">
                            {{ __('ui.project_pricing') }}
                        </x-whatsapp-button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== 2. CATEGORY TABS + PRODUCTS ========== --}}
    <section class="d-sec">
        <div class="d-container">

            @if ($tabs->isNotEmpty())
                {{-- Tabs --}}
                <div class="d-tabs d-rv" style="margin-bottom:32px;" role="tablist" aria-label="{{ __('ui.brand_products_eyebrow') }}">
                    <a href="{{ $tabUrl('all') }}"
                       class="d-tab"
                       aria-selected="{{ (! $catFilter || $catFilter === 'all') ? 'true' : 'false' }}">
                        {{ __('ui.all_brands') }}
                        <span style="font:600 12px/1 var(--font-body);color:var(--fg-3);">
                            ({{ $products->total() }})
                        </span>
                    </a>
                    @foreach ($tabs as $tab)
                        <a href="{{ $tabUrl((string) $tab->id) }}"
                           class="d-tab"
                           aria-selected="{{ $catFilter === (string) $tab->id ? 'true' : 'false' }}">
                            {{ $tab->getTranslation('name', $locale) }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Product grid --}}
            @if ($products->isNotEmpty())
                <div class="d-brand-products-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;">
                    @foreach ($products as $i => $product)
                        <div style="--rv-d:{{ ($i % 4) * 60 }}ms;">
                            @include('site._product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
                @if ($products->hasPages())
                    <div style="margin-top:40px;padding-top:16px;border-top:1px solid var(--border-1);">
                        <x-pagination
                            :page="$products->currentPage()"
                            :total="$products->lastPage()"
                            :count="$products->total()"
                            :per-page="$products->perPage()"
                        />
                    </div>
                @endif
            @else
                <p class="t-body" style="color:var(--fg-2);text-align:center;padding:60px 0;">
                    {{ __('ui.no_products_brand') }}
                </p>
            @endif

        </div>
    </section>

    {{-- ========== 3. SPECIFIED BY DOTS ========== --}}
    @if ($projects->isNotEmpty())
        <section class="d-sec d-sec--subtle">
            <div class="d-container">
                <div class="d-rv d-sec__head" style="margin-bottom:28px;">
                    <x-eyebrow>{{ __('ui.projects') }}</x-eyebrow>
                    <h2 class="d-sec__title t-h2" style="margin:0;">{{ __('ui.specified_by_dots') }}</h2>
                </div>
                <div class="d-brand-projects-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px;">
                    @foreach ($projects as $i => $project)
                        @php
                            $pImg  = $project->getFirstMediaUrl('cover');
                            $pSlug = $project->getTranslation('slug', $locale);
                            $pTitle = $project->getTranslation('title', $locale);
                        @endphp
                        <div class="d-rv">
                            <a href="{{ route('project.show', [$locale, $pSlug]) }}"
                               style="display:block;text-decoration:none;border-radius:var(--radius-lg);overflow:hidden;position:relative;"
                               aria-label="{{ $pTitle }}">
                                <x-image-frame
                                    :src="$pImg ?: null"
                                    :alt="$pTitle"
                                    :ratio="$i === 0 ? 'auto' : '4/3'"
                                    :style="$i === 0 ? 'min-height:340px;' : ''"
                                />
                                <div style="position:absolute;bottom:0;inset-inline:0;padding:16px 20px;background:linear-gradient(0deg,rgba(24,24,22,.72),transparent);color:var(--white);">
                                    <p class="t-body" style="margin:0;font-weight:700;font-size:15px;">{{ $pTitle }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-site.quote-band />

</x-layouts.app>
