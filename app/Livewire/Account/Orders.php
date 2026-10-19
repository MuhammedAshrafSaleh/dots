<?php

namespace App\Livewire\Account;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/** The customer's order history — most recent first, with an empty state. */
class Orders extends Component
{
    public function render(): View
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        /** @var Collection<int, Order> $orders */
        $orders = $customer->orders()
            ->with(['items'])
            ->latest('placed_at')
            ->latest('id')
            ->get();

        $counts = [
            'orders' => $orders->count(),
            'wishlist' => $customer->wishlist()->count(),
            'addresses' => $customer->addresses()->count(),
        ];

        return view('livewire.account.orders', [
            'orders' => $orders,
            'counts' => $counts,
            'locale' => app()->getLocale(),
        ])->layout('components.layouts.app', ['title' => __('account.nav.orders')]);
    }
}
