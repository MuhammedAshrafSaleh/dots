{{-- Placeholder for public pages not yet built. Each later Phase 5/6 task
     replaces the matching route below with the real screen; the global layout
     (header, footer, motion) is already live so these render in context. --}}
<x-layouts.app :title="__('ui.under_construction')">
    <section style="min-height:48vh;display:grid;place-content:center;justify-items:center;gap:14px;text-align:center;padding:64px 24px;">
        <x-eyebrow>DOTS</x-eyebrow>
        <h1 class="t-h1">{{ __('ui.under_construction') }}</h1>
        <p class="t-body-lg" style="color:var(--fg-2);max-width:48ch;">{{ __('ui.under_construction_lead') }}</p>
    </section>

    <x-site.quote-band />
</x-layouts.app>
