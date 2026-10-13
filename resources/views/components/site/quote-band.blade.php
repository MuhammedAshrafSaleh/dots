@php
    // Projects & trade quote band, from the global.quote_band section. BOQ is a
    // logged WhatsApp link (no upload control — project rule). Mirror of
    // Shared.jsx KQuoteBand. Rendered inside a subtle, tight section.
    use App\Models\PageSection;
    use App\Services\WhatsApp;

    $band = PageSection::where('page', 'global')->where('key', 'quote_band')->first();
    if (! $band) {
        return;
    }
    $bullets = $band->field('bullets', $locale) ?: [];
    $quoteLink = app(WhatsApp::class)->link('general', [], $locale);
@endphp
<section class="d-qb-sec">
    <div class="d-container">
        <div class="d-rv d-qb">
            <div style="display:grid;gap:12px;">
                <x-eyebrow>{{ $band->field('eyebrow', $locale) }}</x-eyebrow>
                <h2 class="t-h3" style="margin:0;">{{ $band->field('title', $locale) }}</h2>
                <ul class="d-node-list" style="font:600 14px/1.4 var(--font-body);color:var(--fg-2);grid-template-columns:1fr 1fr;">
                    @foreach ($bullets as $bullet)
                        <li>{{ $bullet['text'] ?? '' }}</li>
                    @endforeach
                </ul>
            </div>
            <div style="display:grid;gap:10px;">
                <x-button size="lg" variant="whatsapp" block :href="$quoteLink" target="_blank" rel="noopener">{{ $band->field('button_label', $locale) }}</x-button>
                <span style="font:500 12px var(--font-body);color:var(--fg-3);text-align:center;">{{ $band->field('hours_text', $locale) }}</span>
            </div>
        </div>
    </div>
</section>
