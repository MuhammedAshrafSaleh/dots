<!DOCTYPE html>
{{-- Temporary placeholder home. Real layout arrives in T1.4 / Phase 5. --}}
<html lang="{{ \App\Support\Locale::tag() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DOTS</title>
</head>
<body>
    <main>
        <p>{{ app()->getLocale() }}</p>
        <a href="{{ \App\Support\Locale::switchUrl(\App\Support\Locale::other()) }}" rel="alternate" hreflang="{{ \App\Support\Locale::tag(\App\Support\Locale::other()) }}">
            {{ \App\Support\Locale::other() }}
        </a>
    </main>
</body>
</html>
