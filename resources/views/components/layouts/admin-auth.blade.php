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
<body class="d-root" style="min-height: 100vh;">
    <div style="min-height: 100vh; background: var(--bg-subtle); display: grid; place-items: center; padding: 40px 16px; position: relative;">
        <div style="position: absolute; top: 16px; inset-inline-end: 16px;">
            <a class="d-btn d-btn--ghost d-btn--sm"
               href="{{ route('admin.lang', \App\Support\Locale::other()) }}">{{ strtoupper(\App\Support\Locale::other()) }}</a>
        </div>
        <div style="width: 100%; max-width: 420px; display: grid; gap: 24px;">
            <div style="display: flex; align-items: center; gap: 10px; justify-content: center;">
                <strong style="font: 800 24px/1 var(--font-display); letter-spacing: var(--tracking-heading);">DOTS</strong>
                <x-badge tone="dark">{{ __('admin.shell.admin') }}</x-badge>
            </div>
            {{ $slot }}
        </div>
    </div>

    <x-admin.toasts />
</body>
</html>
