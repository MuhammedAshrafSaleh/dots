<?php

namespace App\Livewire\Shop;

use App\Enums\OrderPaymentStatus;
use App\Enums\PaymentMethodCode;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shown when a card payment is declined: offer to retry the card (wired to
 * Paymob in T7.5), or switch to cash on delivery / InstaPay via WhatsApp. The
 * order is kept intact; switching only changes its payment method + status.
 */
class PaymentFailed extends Component
{
    public int $orderId;

    public string $option = 'retry';

    public function mount(string $number): void
    {
        $order = Order::where('number', $number)->first();

        if ($order === null || ! $order->viewableInSession()) {
            throw new NotFoundHttpException;
        }

        $this->orderId = $order->id;
    }

    private function order(): Order
    {
        return Order::with(['items', 'paymentMethod'])->findOrFail($this->orderId);
    }

    public function proceed(): void
    {
        if ($this->option === 'retry') {
            try {
                $url = app(PaymentGateway::class)->createCheckout($this->order());
            } catch (PaymentException $e) {
                $this->dispatch('toast', type: 'error', title: __('ui.pf_gateway_error'));

                return;
            }

            $this->redirect($url);

            return;
        }

        $code = $this->option === 'cod' ? PaymentMethodCode::Cod : PaymentMethodCode::InstapayWhatsapp;
        $method = PaymentMethod::active()->where('code', $code->value)->first();

        if ($method === null) {
            $this->dispatch('toast', type: 'error', title: __('ui.pf_retry_soon'));

            return;
        }

        $order = $this->order();
        $instapay = $code === PaymentMethodCode::InstapayWhatsapp;

        DB::transaction(function () use ($order, $method, $code, $instapay): void {
            $order->update([
                'payment_method_id' => $method->id,
                'payment_method_code' => $code->value,
                'payment_status' => $instapay ? OrderPaymentStatus::AwaitingTransfer : OrderPaymentStatus::Pending,
                'reserved_until' => $instapay
                    ? now()->addHours((int) app(Settings::class)->get('commerce.instapay_hold_hours', 24))
                    : null,
            ]);

            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => ['en' => 'Payment method changed to '.$code->value, 'ar' => 'تم تغيير طريقة الدفع إلى '.$code->value],
                'created_at' => now(),
            ]);
        });

        $this->redirectRoute('order.confirmation', [app()->getLocale(), $order->number], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.shop.payment-failed', [
            'order' => $this->order(),
            'locale' => app()->getLocale(),
        ]);
    }
}
