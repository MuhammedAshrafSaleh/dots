{{-- Temporary placeholder home — now rendered inside the real global layout
     (header, mega menu, footer, motion from T5.1). The real home arrives in T5.3. --}}
<x-layouts.app :title="__('ui.under_construction')">
    <section style="min-height:52vh;display:grid;place-content:center;justify-items:center;gap:14px;text-align:center;padding:48px 24px;">
        <x-eyebrow>DOTS</x-eyebrow>
        <h1 class="t-h1">{{ __('ui.under_construction') }}</h1>
        <p class="t-body-lg" style="color:var(--fg-2);max-width:48ch;">{{ __('ui.under_construction_lead') }}</p>
    </section>

    <x-site.quote-band />
</x-layouts.app>
