@props([
    'src' => null,
    'media' => null,
    'sizes' => null,
    'video' => null,
    'alt' => '',
    'label' => null,
    'ratio' => null,
    'contain' => false,
    'radius' => null,
])
@php
    // Mirror of design/website/src/components/core/ImageFrame.jsx.
    // Shows a labelled placeholder when there is no media or it fails to load.
    //
    // When a Spatie Media item is passed as :media, responsive WebP conversions
    // drive srcset/sizes and the <img> src falls back to the best conversion.
    // :src still works for plain URLs (external images, mail, etc.).
    $srcset = null;
    if ($media instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media) {
        $srcset = \App\Support\ResponsiveImage::srcset($media) ?: null;
        $src = \App\Support\ResponsiveImage::fallbackUrl($media);
    }

    $classes = array_filter(['d-img', $contain ? 'd-img--contain' : null]);
    $styles = [];
    if ($ratio) { $styles[] = "aspect-ratio: {$ratio}"; }
    if ($radius) { $styles[] = "border-radius: {$radius}"; }
    $style = implode('; ', $styles);
@endphp
<div x-data="{ failed: false }" {{ $attributes->class($classes)->merge(['style' => $style]) }}>
    @if ($video)
        <video src="{{ $video }}" @if ($src) poster="{{ $src }}" @endif autoplay muted loop playsinline aria-hidden="true" style="width:100%;height:100%;object-fit:cover;"></video>
    @elseif ($src)
        <img x-show="!failed" src="{{ $src }}" @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes ?? '100vw' }}" @endif alt="{{ $alt }}" loading="lazy" decoding="async" x-on:error="failed = true">
    @endif
    @if ($src)
        <div class="d-img__ph" x-show="failed" x-cloak aria-hidden="true">@if ($label)<span class="d-img__label">{{ $label }}</span>@endif</div>
    @elseif (! $video)
        <div class="d-img__ph" aria-hidden="true">@if ($label)<span class="d-img__label">{{ $label }}</span>@endif</div>
    @endif
    {{ $slot }}
</div>
