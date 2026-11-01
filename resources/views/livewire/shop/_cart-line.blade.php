@php
    /**
     * @var \App\Models\CartItem $item
     * @var string $locale
     *
     * Used inside CartDrawer and CartPage — both expose updateQty()/removeItem().
     */
    $variant = $item->productVariant;
    $product = $variant->product;
    $name = $product->getTranslation('name', $locale);
    $img = $product->getFirstMedia('gallery');
    $lineTotal = round((float) $variant->price * $item->qty, 2);
    // Cap the stepper at what is actually in stock (mirrors the PDP cap).
    $stock = (int) $variant->stock_qty;
    $atStockCap = $stock > 0 && $item->qty >= $stock;
    $variantLabel = $variant->attributeValues
        ->map(fn ($av) => $av->getTranslation('label', $locale))
        ->implode(' · ');
@endphp

<div class="d-line" wire:key="line-{{ $item->id }}">
    <div class="d-line__img">
        <x-image-frame :media="$img" sizes="80px" :alt="$name" />
    </div>
    <div>
        <div class="d-line__name">{{ $name }}</div>
        @if ($variantLabel)
            <div class="d-line__var">{{ $variantLabel }}</div>
        @endif
        <div class="d-line__row">
            <div class="d-stepper d-stepper--sm" role="group" aria-label="{{ __('ui.quantity') }}">
                <button type="button"
                    wire:click="updateQty({{ $item->id }}, {{ $item->qty - 1 }})"
                    wire:loading.attr="disabled"
                    aria-label="{{ __('ui.decrease_quantity') }}">
                    <x-icon name="minus" size="14" />
                </button>
                <input type="number" value="{{ $item->qty }}" min="1" max="{{ $stock > 0 ? $stock : 99 }}" readonly
                    aria-label="{{ __('ui.quantity') }}">
                <button type="button"
                    wire:click="updateQty({{ $item->id }}, {{ $item->qty + 1 }})"
                    wire:loading.attr="disabled"
                    @if ($atStockCap) disabled aria-disabled="true" @endif
                    aria-label="{{ __('ui.increase_quantity') }}">
                    <x-icon name="plus" size="14" />
                </button>
            </div>
            <button type="button" class="d-line__rm"
                wire:click="removeItem({{ $item->id }})"
                wire:loading.attr="disabled">
                <x-icon name="trash-2" size="16" />{{ __('ui.remove') }}
            </button>
        </div>
        @if ($atStockCap)
            <div style="margin-top:6px;font:600 12px/1.4 var(--font-body);color:var(--warning-600);">
                {{ __('ui.pdp_stock_cap', ['n' => $stock]) }}
            </div>
        @endif
    </div>
    <div class="d-line__price">{{ number_format($lineTotal, 0) }} {{ __('ui.egp') }}</div>
</div>
