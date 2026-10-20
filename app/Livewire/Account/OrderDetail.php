<?php

namespace App\Livewire\Account;

use App\Models\Customer;
use App\Models\Order;
use App\Support\Orders\StatusTimeline;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * A single order's detail: status timeline, items, payment summary, delivery
 * address and method, with WhatsApp help. Scoped to the owner —
 * another customer's order 404s.
 */
class OrderDetail extends Component
{
    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        abort_if($order->customer_id !== $customer->id, 404);

        $this->orderId = $order->id;
    }

    private function order(): Order
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        /** @var Order $order */
        $order = $customer->orders()
            ->with(['items.product', 'statusHistories', 'address.governorate', 'paymentMethod'])
            ->findOrFail($this->orderId);

        return $order;
    }

    public function render(): View
    {
        $order = $this->order();

        return view('livewire.account.order-detail', [
            'order' => $order,
            'timeline' => StatusTimeline::build($order),
            'stepIndex' => StatusTimeline::currentStep($order),
            'stepCount' => count(StatusTimeline::LADDER),
            'locale' => app()->getLocale(),
        ])->layout('components.layouts.app', ['title' => __('account.nav.orders').' · '.$order->number]);
    }
}
