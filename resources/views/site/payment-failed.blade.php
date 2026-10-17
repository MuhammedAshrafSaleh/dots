<x-layouts.app :title="__('ui.pf_eyebrow')">
    <x-seo :title="__('ui.pf_eyebrow')" :noindex="true" />
    @livewire('shop.payment-failed', ['number' => $number])
</x-layouts.app>
