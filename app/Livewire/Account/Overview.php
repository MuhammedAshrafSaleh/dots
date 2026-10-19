<?php

namespace App\Livewire\Account;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/** Account landing: welcome, latest order snapshot, wishlist preview, quick tiles. */
class Overview extends Component
{
    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer;
    }

    public function render(): View
    {
        $customer = $this->customer();

        /** @var ?Order $latestOrder */
        $latestOrder = $customer->orders()
            ->with(['items', 'statusHistories', 'paymentMethod'])
            ->latest('placed_at')
            ->latest('id')
            ->first();

        /** @var Collection<int, Product> $wishlist */
        $wishlist = $customer->wishlist()
            ->with('brand')
            ->orderByDesc('wishlists.created_at')
            ->get();

        $counts = [
            'orders' => $customer->orders()->count(),
            'wishlist' => $wishlist->count(),
            'addresses' => $customer->addresses()->count(),
        ];

        return view('livewire.account.overview', [
            'customer' => $customer,
            'latestOrder' => $latestOrder,
            'wishlist' => $wishlist,
            'counts' => $counts,
            'locale' => app()->getLocale(),
        ])->layout('components.layouts.app', ['title' => __('account.nav.account')]);
    }
}
