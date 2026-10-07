<!DOCTYPE html>
{{-- T1.3 styleguide: verifies the ported design CSS, fonts and the x-icon
     component render the design primitives in both directions. Local env only.
     The real Blade component library lands in T1.4. --}}
<html lang="{{ \App\Support\Locale::tag() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DOTS — Styleguide</title>
    @vite(['resources/css/site.css', 'resources/js/app.js'])
</head>
<body class="d-root" style="padding: 32px; display: grid; gap: 28px;">
    <header style="display:flex; align-items:center; gap:16px; justify-content:space-between;">
        <span class="d-eyebrow">Styleguide — {{ app()->getLocale() }}</span>
        <a class="d-btn d-btn--ghost d-btn--sm" href="{{ \App\Support\Locale::switchUrl(\App\Support\Locale::other()) }}">
            <x-icon name="arrow-right" /> {{ \App\Support\Locale::other() }}
        </a>
    </header>

    <section style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        <button class="d-btn d-btn--primary">Primary <x-icon name="arrow-right" /></button>
        <button class="d-btn d-btn--dark">Dark</button>
        <button class="d-btn d-btn--outline">Outline</button>
        <button class="d-btn d-btn--ghost">Ghost</button>
        <button class="d-btn d-btn--whatsapp"><x-icon name="whatsapp" /> WhatsApp</button>
        <button class="d-btn d-btn--primary d-btn--sm">Small</button>
        <button class="d-btn d-btn--primary d-btn--lg">Large</button>
        <button class="d-btn d-btn--primary" disabled>Disabled</button>
        <button class="d-btn d-btn--outline d-btn--icon" aria-label="next"><x-icon name="chevron-right" /></button>
    </section>

    <section style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
        <span class="d-badge d-badge--accent">Accent</span>
        <span class="d-badge d-badge--dark">Dark</span>
        <span class="d-badge d-badge--neutral">Neutral</span>
        <span class="d-badge d-badge--outline">Outline</span>
        <span class="d-badge d-badge--success">Success</span>
        <span class="d-badge d-badge--warning">Warning</span>
        <span class="d-badge d-badge--error">Error</span>
        <span class="d-badge d-badge--info">Info</span>
        <span class="d-badge d-badge--sale">Sale</span>
    </section>

    <section style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
        <button class="d-chip">Chip</button>
        <button class="d-chip d-chip--active">Active <span class="d-chip__x"><x-icon name="x" size="14" /></span></button>
    </section>

    <section style="display:grid; gap:16px; max-width:420px;">
        <div class="d-field">
            <label class="d-label" for="f1">Label <span class="d-label__opt">(optional)</span></label>
            <div class="d-control"><input id="f1" class="d-input" placeholder="Placeholder"></div>
            <span class="d-help">Helper text</span>
        </div>
        <div class="d-field">
            <label class="d-label" for="f2">With error</label>
            <div class="d-control"><input id="f2" class="d-input" aria-invalid="true" value="Bad value"></div>
            <span class="d-help d-help--error"><x-icon name="alert-circle" size="14" /> Something is wrong</span>
        </div>
        <div class="d-field">
            <label class="d-label" for="f3">Disabled</label>
            <div class="d-control"><input id="f3" class="d-input" disabled value="Disabled"></div>
        </div>
        <div class="d-field">
            <label class="d-label" for="f4">Textarea</label>
            <div class="d-control"><textarea id="f4" class="d-input" placeholder="Message"></textarea></div>
        </div>
    </section>
</body>
</html>
