@props([
    'title' => null,
    'bodyClass' => '',
])
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::tag() }}" dir="{{ \App\Support\Locale::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — DOTS' : 'DOTS' }}</title>
    @vite(['resources/css/site.css', 'resources/js/app.js'])
    {{-- Per-page head (meta, canonical, hreflang, OG) — filled from Phase 5 SEO. --}}
    {{ $head ?? '' }}
    @stack('head')
</head>
<body {{ $attributes->class(['d-root', $bodyClass]) }}>
    <a class="d-skip" href="#main">{{ __('ui.skip_to_content') }}</a>

    {{-- Header / footer land in Phase 5 (T5.1). Layout is the shell for now. --}}
    <main id="main">
        {{ $slot }}
    </main>

    {{-- Toasts live-region; populated by Livewire/Alpine events. --}}
    <div class="d-toasts" aria-live="polite" aria-atomic="false">
        {{ $toasts ?? '' }}
    </div>
</body>
</html>
