<?php

namespace App\Livewire\Shop;

use App\Services\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Full cart page: line editing, coupon apply/remove, totals and suggestions. */
class CartPage extends Component
{
    public string $couponCode = '';

    public ?string $couponError = null;

    /** @var array<string, mixed> */
    public array $couponErrorParams = [];

    public function mount(): void
    {
        $code = session('cart.coupon');
        $this->couponCode = is_string($code) ? $code : '';
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

    public function applyCoupon(): void
    {
        $result = app(CartManager::class)->applyCoupon($this->couponCode);

        $this->couponError = $result->error;
        $this->couponErrorParams = $result->errorParams;

        $this->notifyCartChanged();
    }

    public function removeCoupon(): void
    {
        app(CartManager::class)->removeCoupon();
        $this->couponCode = '';
        $this->couponError = null;
        $this->couponErrorParams = [];

        $this->notifyCartChanged();
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-render to reflect changes made from the drawer (e.g. suggestion added).
    }

    private function notifyCartChanged(): void
    {
        $this->dispatch('cart-updated')->to(CartButton::class);
        $this->dispatch('cart-updated')->to(CartDrawer::class);
    }

    public function render(): View
    {
        $cart = app(CartManager::class);

        return view('livewire.shop.cart-page', [
            'items' => $cart->items(),
            'totals' => $cart->totals(),
            'suggestions' => $cart->suggestions(),
            'locale' => app()->getLocale(),
        ]);
    }
}
