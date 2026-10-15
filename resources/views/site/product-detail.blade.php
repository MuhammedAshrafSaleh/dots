@php
    /**
     * @var \App\Models\Product $product
     * @var \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection $gallery
     * @var \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection $files
     * @var \Illuminate\Database\Eloquent\Collection $specs
     * @var string|null $overview
     * @var string|null $deliveryHtml
     * @var string|null $returnsHtml
     * @var string $locale
     */

    $productName  = $product->getTranslation('name', $locale);
    $metaTitle    = $product->getTranslation('meta_title', $locale) ?: $productName;
    $metaDesc     = $product->getTranslation('meta_description', $locale);
    $firstImg     = $gallery->first()?->getUrl() ?? '';

    $breadcrumbs  = array_values(array_filter([
        ['label' => __('ui.home'), 'url' => route('home', $locale)],
        $product->category?->parent ? [
            'label' => $product->category->parent->getTranslation('name', $locale),
            'url'   => route('products', $locale).'?cat='.$product->category->parent_id,
        ] : null,
        $product->category ? [
            'label' => $product->category->getTranslation('name', $locale),
            'url'   => route('category', [$locale, $product->category->getTranslation('slug', $locale)]),
        ] : null,
        ['label' => $productName],
    ]));

    $hasTabs       = $overview || $specs->isNotEmpty() || $files->isNotEmpty() || $deliveryHtml || $returnsHtml;
    $hasRelated    = $product->relatedProducts->isNotEmpty();
    $filesCount    = $files->count();
    $previewFiles  = $files->take(3);
@endphp
<x-layouts.app :title="$metaTitle">
<x-seo
    :title="$metaTitle"
    :description="$metaDesc"
    :image="$firstImg" />

{{-- Breadcrumb bar --}}
<div style="background:var(--bg-subtle);border-bottom:1px solid var(--border-1);">
    <div class="d-container" style="padding-block:16px;">
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>
</div>

{{-- ═══════════════════ MAIN TWO-COLUMN ═══════════════════ --}}
<div class="d-container">
    <div class="d-pdp">

        {{-- ── Left: Gallery ─────────────────────────────────────── --}}
        <div x-data="{ img: 0 }">
            <div class="d-pdp__gallery">
                {{-- Desktop thumbnails column --}}
                @if ($gallery->count() > 1)
                    <div class="d-pdp__thumbs">
                        @foreach ($gallery as $i => $media)
                            <button type="button"
                                class="d-pdp__thumb"
                                :class="img === {{ $i }} ? 'is-active' : ''"
                                @click="img = {{ $i }}"
                                aria-label="{{ $media->getCustomProperty('caption') ?: ($productName.' '.($i+1)) }}">
                                <x-image-frame
                                    :src="$media->getUrl('thumb')"
                                    :alt="$media->getCustomProperty('caption') ?: $productName"
                                    ratio="1 / 1" />
                            </button>
                        @endforeach
                    </div>
                @else
                    <div></div>{{-- keep grid columns intact --}}
                @endif

                {{-- Main image --}}
                <div>
                    <div class="d-pdp__main">
                        @if ($gallery->isNotEmpty())
                            @foreach ($gallery as $i => $media)
                                <div x-show="img === {{ $i }}"
                                    @if ($i > 0) x-cloak @endif
                                    style="animation: d-fade-in 240ms var(--ease-out)">
                                    <x-image-frame
                                        :src="$media->getUrl()"
                                        :alt="$media->getCustomProperty('caption') ?: $productName"
                                        ratio="1 / 1"
                                        :label="$media->getCustomProperty('caption') ?: ''" />
                                </div>
                            @endforeach

                            {{-- Badges overlay --}}
                            @php
                                $salePct = null;
                                if ($product->min_price && $product->max_compare_at && $product->max_compare_at > $product->min_price) {
                                    $salePct = (int) round((1 - (float) $product->min_price / (float) $product->max_compare_at) * 100);
                                }
                            @endphp
                            <div style="position:absolute;top:16px;inset-inline-start:16px;display:flex;gap:6px;flex-wrap:wrap;">
                                @if ($salePct)
                                    <span class="d-badge d-badge--sale">{{ __('ui.pcard_sale_off', ['pct' => $salePct]) }}</span>
                                @elseif ($product->is_new)
                                    <x-badge tone="accent">{{ __('ui.tab_new') }}</x-badge>
                                @elseif ($product->is_best_seller)
                                    <x-badge tone="dark">{{ __('ui.tab_best') }}</x-badge>
                                @endif
                            </div>
                        @else
                            <x-image-frame ratio="1 / 1" :alt="$productName" />
                        @endif
                    </div>

                    {{-- Mobile pagination dots --}}
                    @if ($gallery->count() > 1)
                        <div class="d-pdp__dots">
                            @foreach ($gallery as $i => $media)
                                <button type="button"
                                    class="d-pdp__dot"
                                    :class="img === {{ $i }} ? 'is-active' : ''"
                                    @click="img = {{ $i }}"
                                    aria-label="{{ $i + 1 }}">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick downloads strip below gallery (max 3) --}}
            @if ($previewFiles->isNotEmpty())
                <div style="margin-top:20px;display:grid;gap:10px;">
                    <span class="d-variant__head">{{ __('ui.pdp_downloads') }}</span>
                    <div class="d-files">
                        @foreach ($previewFiles as $file)
                            @php
                                $ext   = pathinfo($file->file_name, PATHINFO_EXTENSION);
                                $title = $file->getCustomProperty('title') ?: $file->name;
                                $type  = $file->getCustomProperty('type') ?: strtoupper($ext);
                            @endphp
                            <a href="{{ $file->getUrl() }}"
                               class="d-file"
                               download
                               target="_blank"
                               rel="noopener noreferrer">
                                <span class="d-file__ico">
                                    <x-icon name="file" size="20" />
                                    <span class="d-file__ext">{{ strtoupper($ext) }}</span>
                                </span>
                                <span>
                                    <span class="d-file__name">{{ $title }}</span>
                                    <span class="d-file__meta">{{ $type }} · {{ $file->human_readable_size }}</span>
                                </span>
                                <span class="d-file__dl"><x-icon name="download" size="16" /></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Right: Info + VariantPicker ──────────────────────── --}}
        <div class="d-pdp__info">
            @livewire('shop.variant-picker', ['product' => $product])
        </div>

    </div>
</div>

@if ($hasTabs)
{{-- ═══════════════════ TABS SECTION ═══════════════════ --}}
<section class="d-pdp__tabs-sec d-sec--subtle" x-data="{ tab: 'overview' }">
    <div class="d-container">

        {{-- Tab bar --}}
        <div class="d-tabs" role="tablist">
            @if ($overview)
                <button type="button"
                    class="d-tab"
                    role="tab"
                    :aria-selected="tab === 'overview' ? 'true' : 'false'"
                    @click="tab = 'overview'">
                    {{ __('ui.pdp_overview') }}
                </button>
            @endif
            @if ($specs->isNotEmpty())
                <button type="button"
                    class="d-tab"
                    role="tab"
                    :aria-selected="tab === 'specs' ? 'true' : 'false'"
                    @click="tab = 'specs'">
                    {{ __('ui.pdp_specifications') }}
                </button>
            @endif
            @if ($files->isNotEmpty())
                <button type="button"
                    class="d-tab"
                    role="tab"
                    :aria-selected="tab === 'downloads' ? 'true' : 'false'"
                    @click="tab = 'downloads'">
                    {{ __('ui.pdp_downloads') }}
                    @if ($filesCount > 0)
                        <span class="d-tab__n">{{ $filesCount }}</span>
                    @endif
                </button>
            @endif
            @if ($deliveryHtml || $returnsHtml)
                <button type="button"
                    class="d-tab"
                    role="tab"
                    :aria-selected="tab === 'delivery' ? 'true' : 'false'"
                    @click="tab = 'delivery'">
                    {{ __('ui.pdp_delivery_returns') }}
                </button>
            @endif
        </div>

        {{-- Tab panels --}}
        <div class="d-pdp__tabs-body">
            <div class="d-pdp__tab-panel" role="tabpanel">

                {{-- Overview --}}
                @if ($overview)
                    <div x-show="tab === 'overview'">
                        <div class="d-prose" style="max-width:none;">
                            {!! $overview !!}
                        </div>
                    </div>
                @endif

                {{-- Specifications --}}
                @if ($specs->isNotEmpty())
                    <div x-show="tab === 'specs'" @if ($overview) x-cloak @endif>
                        <table class="d-spec" aria-label="{{ __('ui.pdp_specifications') }}">
                            <tbody>
                                @foreach ($specs as $spec)
                                    <tr>
                                        <th scope="row">{{ $spec->getTranslation('name', $locale) }}</th>
                                        <td>{{ $spec->getTranslation('value', $locale) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Downloads --}}
                @if ($files->isNotEmpty())
                    <div x-show="tab === 'downloads'" @if ($overview || $specs->isNotEmpty()) x-cloak @endif>
                        <div class="d-files">
                            @foreach ($files as $file)
                                @php
                                    $ext   = pathinfo($file->file_name, PATHINFO_EXTENSION);
                                    $title = $file->getCustomProperty('title') ?: $file->name;
                                    $type  = $file->getCustomProperty('type') ?: strtoupper($ext);
                                @endphp
                                <a href="{{ $file->getUrl() }}"
                                   class="d-file"
                                   download
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <span class="d-file__ico">
                                        <x-icon name="file" size="20" />
                                        <span class="d-file__ext">{{ strtoupper($ext) }}</span>
                                    </span>
                                    <span>
                                        <span class="d-file__name">{{ $title }}</span>
                                        <span class="d-file__meta">{{ $type }} · {{ $file->human_readable_size }}</span>
                                    </span>
                                    <span class="d-file__dl"><x-icon name="download" size="16" /></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div x-show="tab === 'downloads'" x-cloak>
                        <p class="t-body" style="margin:0;color:var(--fg-3);">{{ __('ui.pdp_no_files') }}</p>
                    </div>
                @endif

                {{-- Delivery & returns --}}
                @if ($deliveryHtml || $returnsHtml)
                    <div x-show="tab === 'delivery'"
                         @if ($overview || $specs->isNotEmpty() || $files->isNotEmpty()) x-cloak @endif>
                        <div class="d-prose" style="max-width:none;">
                            @if ($deliveryHtml)
                                {!! $deliveryHtml !!}
                            @endif
                            @if ($returnsHtml)
                                {!! $returnsHtml !!}
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar image (second gallery item) --}}
            @if ($gallery->count() > 1)
                <div class="d-compact-hide">
                    <x-image-frame
                        :src="$gallery->get(1)?->getUrl()"
                        :alt="$productName"
                        ratio="1 / 1"
                        radius="var(--radius-lg)"
                        style="background:var(--white);" />
                </div>
            @else
                <div></div>
            @endif
        </div>

    </div>
</section>
@endif

@if ($hasRelated)
{{-- ═══════════════════ RELATED PRODUCTS ═══════════════════ --}}
<section class="d-sec">
    <div class="d-container">
        <div class="d-sec__head">
            <div class="d-sec__head-text">
                <span class="d-sec__eyebrow t-overline">{{ __('ui.pdp_related_eyebrow') }}</span>
                <h2 class="d-sec__title">{{ __('ui.pdp_related_title') }}</h2>
            </div>
        </div>
        <div class="d-pgrid d-pgrid--2">
            @foreach ($product->relatedProducts as $related)
                @include('site._product-card', ['product' => $related, 'locale' => $locale])
            @endforeach
        </div>
    </div>
</section>
@endif

</x-layouts.app>
