<?php

namespace App\Livewire\Shop;

use App\Services\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Header cart icon with a live item count; opens the cart drawer on click. */
class CartButton extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = app(CartManager::class)->itemCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $this->count = app(CartManager::class)->itemCount();
    }

    public function render(): View
    {
        return view('livewire.shop.cart-button');
    }
}
