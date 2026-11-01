<x-layouts.app :title="__('ui.cart')">
<x-seo :title="__('ui.cart')" />

@if (session()->has('cart_notices'))
    <div class="d-container" style="padding-top:24px;display:grid;gap:12px;" role="status" aria-live="polite">
        @foreach (session('cart_notices') as $notice)
            <div style="display:flex;gap:10px;align-items:flex-start;border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--bg-subtle);padding:14px 16px;">
                <x-icon name="info" :size="18" style="color:var(--fg-3);flex:none;margin-top:2px;" />
                <span style="font:500 14px/1.6 var(--font-body);color:var(--fg-2);text-wrap:pretty;">{{ $notice }}</span>
            </div>
        @endforeach
    </div>
@endif

@livewire('shop.cart-page')
</x-layouts.app>
