<?php

namespace App\Livewire\Shop;

use App\Models\Customer;
use App\Support\Wishlist;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Invisible listener mounted once in the site layout. Product-card and PDP
 * hearts dispatch `wishlist-toggle` (via Alpine, optimistically); this adds or
 * removes the saved product for the signed-in customer. Guests never reach it —
 * their heart shows the login prompt instead.
 */
class WishlistSync extends Component
{
    #[On('wishlist-toggle')]
    public function toggle(int $productId): void
    {
        /** @var Customer|null $customer */
        $customer = auth('customer')->user();
        if ($customer === null) {
            return;
        }

        if ($customer->wishlist()->where('product_id', $productId)->exists()) {
            $customer->wishlist()->detach($productId);
        } else {
            $customer->wishlist()->attach($productId, ['created_at' => now()]);
        }

        app(Wishlist::class)->forget();
    }

    public function render(): View
    {
        return view('livewire.shop.wishlist-sync');
    }
}
