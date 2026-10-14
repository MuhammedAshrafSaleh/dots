@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'alternateUrl' => null,
    'type' => 'website',
    'noindex' => false,
])
@php
    use App\Support\Locale;
    use App\Services\Settings;

    $settings  = app(Settings::class);
    $locale    = app()->getLocale();
    $otherLocale = Locale::other($locale);

    $metaDesc = $description ?? $settings->translated('seo.default_description');
    $ogImage  = ($image && $image !== '') ? $image : ($settings->get('seo.og_image') ?: null);
    $fullTitle = ($title && trim($title) !== '') ? trim($title).' — DOTS' : 'DOTS';

    $canonicalUrl = url(request()->getPathInfo());

    $otherPath = ($alternateUrl && $alternateUrl !== '')
        ? $alternateUrl
        : Locale::switchUrl($otherLocale);
    $otherUrl = url($otherPath);

    $arUrl = $locale === 'ar' ? $canonicalUrl : $otherUrl;
    $enUrl = $locale === 'en' ? $canonicalUrl : $otherUrl;
@endphp
@push('head')
@if ($metaDesc)
<meta name="description" content="{{ $metaDesc }}">
@endif
@if ($noindex)
<meta name="robots" content="noindex,nofollow">
@endif
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="alternate" hreflang="ar-EG" href="{{ $arUrl }}">
<link rel="alternate" hreflang="en" href="{{ $enUrl }}">
<link rel="alternate" hreflang="x-default" href="{{ $arUrl }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
@if ($metaDesc)
<meta property="og:description" content="{{ $metaDesc }}">
@endif
@if ($ogImage)
<meta property="og:image" content="{{ $ogImage }}">
@endif
@endpush
