<?php

namespace App\Livewire\Shop;

use App\Services\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Slide-in cart drawer, mounted once in the site layout. */
class CartDrawer extends Component
{
    public bool $open = false;

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    /** Global add path (product cards dispatch this via Livewire.dispatch). */
    #[On('cart-add')]
    public function addItem(int $variantId, int $qty = 1): void
    {
        app(CartManager::class)->add($variantId, $qty);
        $this->open = true;
        $this->notifyCartChanged();
    }

    public function updateQty(int $itemId, int $qty): void
    {
        app(CartManager::class)->updateQty($itemId, $qty);
        $this->notifyCartChanged();
    }

    public function removeItem(int $itemId): void
    {
        app(CartManager::class)->remove($itemId);
        $this->notifyCartChanged();
    }

    private function notifyCartChanged(): void
    {
        $this->dispatch('cart-updated')->to(CartButton::class);
        $this->dispatch('cart-updated')->to(CartPage::class);
    }

    public function render(): View
    {
        $cart = app(CartManager::class);

        return view('livewire.shop.cart-drawer', [
            'items' => $cart->items(),
            'totals' => $cart->totals(),
        ]);
    }
}
