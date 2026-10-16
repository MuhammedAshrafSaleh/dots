@props([
    'title' => null,
])
@php
    $admin = auth('admin')->user();
    $initials = \Illuminate\Support\Str::of($admin?->name ?? 'DOTS')
        ->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $other = \App\Support\Locale::other();
@endphp
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
    style="height: 100vh; overflow: hidden;"
>
    <div class="a-shell">
        <nav class="a-side" aria-label="{{ __('admin.nav.dashboard') }}" x-show="!compact || menu" @click.outside="menu = false" x-cloak>
            <div class="a-side__logo">
                <strong style="font: 800 20px/1 var(--font-display); letter-spacing: var(--tracking-heading);">DOTS</strong>
                <x-badge tone="dark">{{ __('admin.shell.admin') }}</x-badge>
                <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" x-show="compact"
                        @click="menu = false" aria-label="{{ __('admin.shell.close_menu') }}" style="margin-inline-start: auto;">
                    <x-icon name="x" size="16" />
                </button>
            </div>

            <div class="a-side__nav">
                @foreach (config('admin_nav.groups') as $group)
                    @php
                        $items = collect($group['items'])->filter(fn ($i) => $admin?->can($i[4]))->all();
                    @endphp
                    @continue(! $items)
                    @if ($group['label'])<div class="a-side__group">{{ __('admin.nav.'.$group['label']) }}</div>@endif
                    @foreach ($items as [$key, $icon, $labelKey, $routeName, $permission])
                        @php $exists = \Illuminate\Support\Facades\Route::has($routeName); @endphp
                        @if ($exists)
                            <a class="a-side__link" href="{{ route($routeName) }}" wire:navigate
                               @if (request()->routeIs($routeName) || request()->routeIs($routeName.'.*')) aria-current="page" @endif>
                                <x-icon :name="$icon" size="18" />{{ __('admin.nav.'.$labelKey) }}
                            </a>
                        @else
                            <span class="a-side__link" aria-disabled="true" style="opacity: .5; cursor: default;">
                                <x-icon :name="$icon" size="18" />{{ __('admin.nav.'.$labelKey) }}
                                <span class="a-side__n">{{ __('admin.shell.soon') }}</span>
                            </span>
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div class="a-side__foot">
                <a href="{{ url('/'.\App\Support\Locale::DEFAULT) }}" target="_blank" rel="noopener"
                   style="display: inline-flex; align-items: center; gap: 6px; font: 700 13px var(--font-body); color: var(--fg-1); text-decoration: none;">
                    <x-icon name="external-link" size="16" />{{ __('admin.shell.view_store') }}
                </a>
                <span>{{ __('admin.shell.version') }}</span>
            </div>
        </nav>

        <div class="a-main">
            <header class="a-top">
                <button type="button" class="d-btn d-btn--ghost d-btn--icon" x-show="compact" @click="menu = !menu" aria-label="{{ __('admin.shell.open_menu') }}">
                    <x-icon name="menu" />
                </button>

                <livewire:admin.global-search />

                <span style="margin-inline-start: auto;"></span>

                <a class="d-btn d-btn--ghost d-btn--sm" href="{{ route('admin.lang', $other) }}">{{ strtoupper($other) }}</a>

                <div class="a-user" @click.outside="user = false">
                    <button type="button" @click="user = !user" :aria-expanded="user">
                        <span class="a-avatar a-avatar--node">{{ $initials }}</span>
                        <span x-show="!compact"><span>{{ $admin?->name }}</span><span class="a-sub">{{ __('ui.admin_role') }}</span></span>
                        <x-icon name="chevron-down" size="16" x-show="!compact" />
                    </button>
                    <div class="a-menu" x-show="user" x-cloak>
                        <a href="{{ route('admin.dashboard') }}" wire:navigate><x-icon name="user" size="16" />{{ __('admin.shell.my_profile') }}</a>
                        @if (\Illuminate\Support\Facades\Route::has('admin.settings'))
                            <a href="{{ route('admin.settings') }}" wire:navigate><x-icon name="settings" size="16" />{{ __('admin.shell.settings') }}</a>
                        @endif
                        <hr>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="is-danger" style="width: 100%;"><x-icon name="log-out" size="16" />{{ __('admin.shell.log_out') }}</button>
                        </form>
                    </div>
                </div>
            </header>

            {{ $slot }}
        </div>
    </div>

    <x-admin.toasts />
</body>
</html>
