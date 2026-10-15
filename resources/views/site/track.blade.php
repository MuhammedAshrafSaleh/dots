<x-layouts.app :title="__('ui.track_order')">
    <x-seo :title="__('ui.track_order')" :description="__('ui.track_lede')" />

    <section class="d-sec d-sec--subtle">
        <div class="d-container">
            <x-breadcrumbs :items="[
                ['label' => __('ui.home'), 'url' => route('home')],
                ['label' => __('ui.track_order')],
            ]" />
            <div class="d-rv" style="display:grid;gap:12px;padding-top:32px;padding-bottom:24px;max-width:680px;">
                <h1 class="t-h2" style="margin:0;">{{ __('ui.track_order') }}</h1>
                <p style="margin:0;color:var(--fg-2);font:500 16px/1.6 var(--font-body);">{{ __('ui.track_lede') }}</p>
            </div>
        </div>
    </section>

    @livewire('shop.track-order')
</x-layouts.app>
