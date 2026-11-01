<?php

namespace App\Livewire\Admin\Orders;

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Detail extends Component
{
    public Order $order;

    public string $status = '';

    public string $paymentStatus = '';

    public ?string $expectedDelivery = null;

    public string $customerNoteEn = '';

    public string $customerNoteAr = '';

    public string $internalNote = '';

    public function mount(Order $order): void
    {
        $this->authorize('orders.view');

        $this->order = $order;
        $this->status = $order->status->value;
        $this->paymentStatus = $order->payment_status->value;
        $this->expectedDelivery = $order->expected_delivery_at?->format('Y-m-d');
    }

    public function update(UpdateOrderStatus $action): void
    {
        $this->authorize('orders.update');

        $validated = $this->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'paymentStatus' => ['required', Rule::enum(OrderPaymentStatus::class)],
            'expectedDelivery' => ['nullable', 'date'],
            'customerNoteEn' => ['nullable', 'string', 'max:500'],
            'customerNoteAr' => ['nullable', 'string', 'max:500'],
            'internalNote' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = OrderStatus::from($this->status);
        $newPayment = OrderPaymentStatus::from($this->paymentStatus);

        $changed = $newStatus !== $this->order->status
            || $newPayment !== $this->order->payment_status
            || ($this->expectedDelivery !== null && $this->expectedDelivery !== $this->order->expected_delivery_at?->format('Y-m-d'))
            || trim($this->customerNoteEn) !== ''
            || trim($this->customerNoteAr) !== ''
            || trim($this->internalNote) !== '';

        if (! $changed) {
            $this->dispatch('toast', tone: 'info', title: __('admin.orders.nothing_changed'));

            return;
        }

        $shortages = $action->handle(
            $this->order,
            $newStatus,
            $newPayment,
            $validated['expectedDelivery'] ?? null,
            ['en' => $this->customerNoteEn, 'ar' => $this->customerNoteAr],
            $this->internalNote,
            auth('admin')->id(),
        );

        $this->order->refresh();
        $this->reset('customerNoteEn', 'customerNoteAr', 'internalNote');

        if ($shortages !== []) {
            // Reinstated against stock that can't cover the order: warn the admin
            // now so they call the customer. The full shortage is also kept in the
            // order's history (internal note) after this toast disappears.
            $this->dispatch(
                'toast',
                tone: 'warning',
                title: __('admin.orders.reinstate_short_toast'),
                message: collect($shortages)->map(fn (array $s): string => $s['label'])->implode(', '),
            );

            return;
        }

        $this->dispatch('toast', tone: 'success', title: __('admin.orders.updated'), message: $this->order->number);
    }

    /** wa.me link to the customer with the "order update" template prefilled (staff-only, not logged). */
    private function whatsappUpdateUrl(WhatsApp $whatsapp): string
    {
        $locale = $this->order->locale ?: app()->getLocale();
        $statusLabel = __('admin.orders.status_'.$this->order->status->value, [], $locale);

        $message = $whatsapp->message('order_update', [
            'order_number' => $this->order->number,
            'status' => $statusLabel,
            'url' => route('track', ['locale' => $locale]),
        ], $locale);

        return 'https://wa.me/'.WhatsApp::normalizeNumber($this->order->contact_mobile).'?text='.rawurlencode($message);
    }

    public function render(WhatsApp $whatsapp): View
    {
        $this->order->loadMissing([
            'items',
            'address.governorate',
            'coupon',
            'customer',
            'payments' => fn ($q) => $q->orderBy('created_at'),
            'statusHistories.user',
        ]);

        return view('livewire.admin.orders.detail', [
            'order' => $this->order,
            'orderStatuses' => array_map(
                fn (OrderStatus $s) => ['value' => $s->value, 'label' => __('admin.orders.status_'.$s->value)],
                OrderStatus::cases(),
            ),
            'paymentStatuses' => array_map(
                fn (OrderPaymentStatus $s) => ['value' => $s->value, 'label' => __('admin.orders.pay_'.$s->value)],
                OrderPaymentStatus::cases(),
            ),
            'whatsappUrl' => $this->whatsappUpdateUrl($whatsapp),
        ]);
    }
}
