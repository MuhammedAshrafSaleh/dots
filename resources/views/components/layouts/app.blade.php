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

    {{-- Toast stack driven by Alpine `toasts` store (app.js).
         Livewire dispatches and session flashes both push into the same store. --}}
    @php
        $flashToasts = array_values(array_filter([
            session('toast_success') ? ['tone' => 'success', 'message' => session('toast_success')] : null,
            session('toast_info')    ? ['tone' => 'info',    'message' => session('toast_info')]    : null,
        ]));
    @endphp
    <div class="d-toasts" aria-live="polite" aria-atomic="false" x-data="{}"
         @if ($flashToasts) x-init="@foreach ($flashToasts as $ft)$store.toasts.push(@js($ft));@endforeach" @endif
    >
        <template x-for="t in $store.toasts.items" :key="t.id">
            <div
                class="d-toast"
                :class="`d-toast--${t.tone}`"
                :role="t.tone === 'error' ? 'alert' : 'status'"
                x-transition
            >
                <span class="d-toast__ico">
                    <span x-show="t.tone === 'success'"><x-icon name="check" size="16" stroke="2" /></span>
                    <span x-show="t.tone === 'error'"><x-icon name="circle-alert" size="16" stroke="2" /></span>
                    <span x-show="t.tone === 'warning'"><x-icon name="triangle-alert" size="16" stroke="2" /></span>
                    <span x-show="t.tone === 'info'"><x-icon name="info" size="16" stroke="2" /></span>
                    <span x-show="t.tone === 'cart'"><x-icon name="shopping-bag" size="16" stroke="2" /></span>
                </span>
                <div style="min-width: 0;">
                    <div class="d-toast__title" x-show="t.title" x-text="t.title"></div>
                    <div class="d-toast__msg" x-show="t.message" x-text="t.message"></div>
                </div>
                <button type="button" class="d-toast__x" @click="$store.toasts.dismiss(t.id)" aria-label="{{ __('ui.dismiss') }}">
                    <x-icon name="x" size="16" />
                </button>
            </div>
        </template>
    </div>
</body>
</html>
