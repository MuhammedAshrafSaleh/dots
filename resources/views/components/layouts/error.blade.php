@props([
    'code' => '',
    'title' => '',
])
{{-- Self-contained error layout (T9.4). Deliberately does NOT render the site
     header/footer or hit the DB, so it still renders when the app is broken
     (500/503). Styled with design tokens from site.css. --}}
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::tag() }}" dir="{{ \App\Support\Locale::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — DOTS</title>
    @vite(['resources/css/site.css'])
</head>
<body class="d-root">
    <main style="min-height:100vh;display:grid;place-content:center;justify-items:center;gap:18px;text-align:center;padding:64px 24px;">
        <span style="color:var(--fg-2);letter-spacing:.14em;font-weight:700;font-size:13px;text-transform:uppercase;">DOTS</span>
        <p style="font-size:clamp(56px,12vw,120px);line-height:1;font-weight:700;color:var(--accent,currentColor);margin:0;">{{ $code }}</p>
        <h1 class="t-h2" style="margin:0;">{{ $title }}</h1>
        <p class="t-body-lg" style="color:var(--fg-2);max-width:46ch;margin:0;">{{ $slot }}</p>
        <a class="d-btn d-btn--primary" href="{{ url('/'.\App\Support\Locale::DEFAULT) }}" style="margin-top:8px;">
            {{ __('errors.back_home') }}
        </a>
    </main>
</body>
</html>
