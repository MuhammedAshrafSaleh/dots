@props([
    'title' => null,
    'bodyClass' => '',
    'active' => null,
])
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::tag() }}" dir="{{ \App\Support\Locale::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — DOTS' : 'DOTS' }}</title>
    {{-- Set JS/compact/preloader flags synchronously before paint (no FOUC). --}}
    <script>
        (function () {
            var h = document.documentElement;
            h.classList.add('d-js');
            try {
                if (window.matchMedia('(max-width: 860px)').matches) h.classList.add('d-compact');
                var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (!reduce && !sessionStorage.getItem('dots-pre')) h.classList.add('d-preload');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/site.css', 'resources/js/app.js'])
    {{-- Per-page head (meta, canonical, hreflang, OG) — filled from Phase 5 SEO. --}}
    {{ $head ?? '' }}
    @stack('head')
</head>
<body {{ $attributes->class(['d-root', $bodyClass]) }}>
    <a class="d-skip" href="#main">{{ __('ui.skip_to_content') }}</a>

    <x-site.header :active="$active" />

    <main id="main" class="d-pagein">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.motion />

    {{-- Cart drawer (opens on add-to-cart / header cart icon). --}}
    @livewire('shop.cart-drawer')

    {{-- Wishlist toggle listener for product-card / PDP hearts. --}}
    @livewire('shop.wishlist-sync')

    {{-- Toasts live-region; populated by Livewire/Alpine events or redirect flash. --}}
    <div class="d-toasts" aria-live="polite" aria-atomic="false">
        {{ $toasts ?? '' }}
        @if (session('toast_success'))
            <x-toast tone="success" :message="session('toast_success')" />
        @endif
        @if (session('toast_info'))
            <x-toast tone="info" :message="session('toast_info')" />
        @endif
    </div>
</body>
</html>
