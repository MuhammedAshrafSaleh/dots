{{-- Temporary placeholder home. Rendered inside the real layout (CSS/JS loaded)
     with a minimal header + footer shell so a bare page never reads as "broken".
     Replaced by the real home in Phase 5 (T5.3). --}}
<x-layouts.app :title="__('ui.under_construction')">
    {{-- Minimal header shell (real header/mega-menu arrives in Phase 5 / T5.1). --}}
    <header style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 24px;border-bottom:1px solid var(--border-1);">
        <strong style="font:800 20px/1 var(--font-display);letter-spacing:-0.02em;">DOTS</strong>
        <a class="d-btn d-btn--ghost d-btn--sm" href="{{ \App\Support\Locale::switchUrl(\App\Support\Locale::other()) }}" hreflang="{{ \App\Support\Locale::tag(\App\Support\Locale::other()) }}">
            {{ strtoupper(\App\Support\Locale::other()) }}
        </a>
    </header>

    <section style="min-height:52vh;display:grid;place-content:center;justify-items:center;gap:14px;text-align:center;padding:48px 24px;">
        <x-eyebrow>DOTS</x-eyebrow>
        <h1 class="t-h1">{{ __('ui.under_construction') }}</h1>
        <p class="t-body-lg" style="color:var(--fg-2);max-width:48ch;">{{ __('ui.under_construction_lead') }}</p>
    </section>

    {{-- Minimal footer shell (real footer arrives in Phase 5 / T5.1). --}}
    <footer style="padding:20px 24px;border-top:1px solid var(--border-1);color:var(--fg-3);font:500 13px/1.5 var(--font-body);">
        © {{ date('Y') }} DOTS. {{ __('ui.all_rights_reserved') }}
    </footer>
</x-layouts.app>
