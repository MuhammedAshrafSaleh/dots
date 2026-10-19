<?php

namespace App\Livewire\Account;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/** Saved products: remove individually, or add every buyable one to the cart. */
class Wishlist extends Component
{
    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer;
    }

    /** @return Collection<int, Product> */
    private function products(): Collection
    {
        return $this->customer()->wishlist()
            ->with(['brand', 'variants.swatchAttributeValues'])
            ->orderByDesc('wishlists.created_at')
            ->get();
    }

    public function remove(int $productId): void
    {
        $this->customer()->wishlist()->detach($productId);
    }

    /** Add the default variant of every priced product to the cart, then open the drawer. */
    public function addAll(CartManager $cart): void
    {
        $added = 0;

        foreach ($this->products() as $product) {
            $variant = $this->defaultVariant($product);
            if ($variant !== null && $variant->price !== null) {
                $cart->add($variant->id, 1);
                $added++;
            }
        }

        if ($added > 0) {
            $this->dispatch('cart-updated');
            $this->dispatch('open-cart-drawer');
            session()->flash('account.flash', trans_choice('account.wishlist.added_all', $added, ['count' => $added]));
        }
    }

    private function defaultVariant(Product $product): ?ProductVariant
    {
        return $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first(fn (ProductVariant $v) => $v->is_active && $v->price !== null)
            ?? $product->variants->first();
    }

    public function render(): View
    {
        $products = $this->products();
        $customer = $this->customer();

        $counts = [
            'orders' => $customer->orders()->count(),
            'wishlist' => $products->count(),
            'addresses' => $customer->addresses()->count(),
        ];

        return view('livewire.account.wishlist', [
            'products' => $products,
            'buyableCount' => $products->filter(fn (Product $p) => $p->min_price !== null)->count(),
            'counts' => $counts,
            'locale' => app()->getLocale(),
        ])->layout('components.layouts.app', ['title' => __('account.nav.wishlist')]);
    }
}
