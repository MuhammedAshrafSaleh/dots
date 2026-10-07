@props([
    'title' => null,
])
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::tag() }}" dir="{{ \App\Support\Locale::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' — DOTS Admin' : 'DOTS Admin' }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body
    x-data="{ compact: window.matchMedia('(max-width: 960px)').matches, menu: false, user: false }"
    x-init="window.matchMedia('(max-width: 960px)').addEventListener('change', e => compact = e.matches)"
    :class="{ 'd-compact': compact }"
    class="d-root"
    style="height: 100vh;"
>
    <div class="a-shell">
        {{-- Sidebar (static shell — nav data wired in Phase 3). --}}
        <nav class="a-side" aria-label="{{ __('ui.admin_navigation') }}" x-show="!compact || menu" @click.outside="menu = false">
            <div class="a-side__logo">
                <strong>DOTS</strong>
            </div>
            <div class="a-side__nav">
                {{ $nav ?? '' }}
            </div>
            <div class="a-side__foot">
                <span>DOTS Admin</span>
            </div>
        </nav>

        <div class="a-main">
            <header class="a-top">
                <button type="button" class="d-btn d-btn--ghost d-btn--icon" x-show="compact" @click="menu = !menu" aria-label="{{ __('ui.open_menu') }}">
                    <x-icon name="menu" />
                </button>
                <span style="margin-inline-start: auto;"></span>

                <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--has-count" aria-label="{{ __('ui.notifications') }}">
                    <x-icon name="bell" />
                    <span class="d-btn__count">3</span>
                </button>

                <a class="d-btn d-btn--ghost d-btn--sm" href="{{ \App\Support\Locale::switchUrl(\App\Support\Locale::other()) }}">{{ strtoupper(\App\Support\Locale::other()) }}</a>

                <div class="a-user" @click.outside="user = false">
                    <button type="button" @click="user = !user" :aria-expanded="user">
                        <span class="a-avatar a-avatar--node">MA</span>
                    </button>
                    <div class="a-menu" x-show="user" x-cloak>
                        <button type="button"><x-icon name="user" size="16" />{{ __('ui.my_profile') }}</button>
                        <button type="button"><x-icon name="settings" size="16" />{{ __('ui.settings') }}</button>
                        <hr>
                        <button type="button" class="is-danger"><x-icon name="log-out" size="16" />{{ __('ui.log_out') }}</button>
                    </div>
                </div>
            </header>

            {{ $slot }}
        </div>
    </div>

    <div class="d-toasts" aria-live="polite" aria-atomic="false">
        {{ $toasts ?? '' }}
    </div>
</body>
</html>
