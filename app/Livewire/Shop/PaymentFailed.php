<?php

namespace App\Livewire\Shop;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\CartManager;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shown when a card payment is declined: offer to retry the card, or switch to
 * cash on delivery / InstaPay via WhatsApp. The order is kept intact; switching
 * only changes its payment method + status.
 *
 * The reservation sweeper can cancel the order in the seconds the customer
 * spends choosing here, so every branch re-loads the order under a row lock and
 * re-checks it is not cancelled before mutating anything. If it was released,
 * nothing is touched and the customer is offered a fresh cart from the order's
 * items.
 */
class PaymentFailed extends Component
{
    public int $orderId;

    public string $option = 'retry';

    /** Set once the sweeper has cancelled the order out from under the page. */
    public bool $expired = false;

    public function mount(string $number): void
    {
        $order = Order::where('number', $number)->first();

        if ($order === null || ! $order->viewableInSession()) {
            throw new NotFoundHttpException;
        }

        $this->orderId = $order->id;
        $this->expired = $order->status === OrderStatus::Cancelled;
    }

    private function order(): Order
    {
        return Order::with(['items', 'paymentMethod'])->findOrFail($this->orderId);
    }

    public function proceed(): void
    {
        if ($this->isCancelled()) {
            $this->expired = true;

            return;
        }

        if ($this->option === 'retry') {
            try {
                // createCheckout refreshes reserved_until, so retrying can never
                // be swept underneath the customer.
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

        $instapay = $code === PaymentMethodCode::InstapayWhatsapp;
        $expired = false;

        DB::transaction(function () use ($method, $code, $instapay, &$expired): void {
            $order = Order::lockForUpdate()->find($this->orderId);

            // Re-check under the lock: the sweeper may have cancelled it while
            // the customer was choosing. If so, mutate nothing.
            if ($order === null || $order->status === OrderStatus::Cancelled) {
                $expired = true;

                return;
            }

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

        if ($expired) {
            $this->expired = true;

            return;
        }

        $this->redirectRoute('order.confirmation', [app()->getLocale(), $this->order()->number], navigate: true);
    }

    /**
     * Re-add the cancelled order's items to the current cart (clamped to current
     * stock) and send the customer to the cart to check out fresh. The order
     * stays cancelled; its coupon is not re-applied and stock is not touched.
     */
    public function reAddToCart(CartManager $cart): void
    {
        $locale = app()->getLocale();
        $result = $cart->reAddFromOrder($this->order(), $locale);
        $separator = $locale === 'ar' ? '، ' : ', ';

        $notices = [];

        if ($result['added'] === 0 && $result['dropped'] !== []) {
            // Nothing could come back — the whole order is unavailable now.
            $notices[] = __('ui.pf_expired_readd_none');
        } else {
            if ($result['dropped'] !== []) {
                $notices[] = __('ui.pf_expired_readd_dropped', ['items' => implode($separator, $result['dropped'])]);
            }
            if ($result['reduced'] !== []) {
                $items = array_map(
                    fn (array $r): string => $r['name'].' ('.$r['requested'].' → '.$r['actual'].')',
                    $result['reduced'],
                );
                $notices[] = __('ui.pf_expired_readd_reduced', ['items' => implode($separator, $items)]);
            }
        }

        // Flash (not a Livewire toast) so the message survives the wire:navigate
        // redirect and renders on the cart page itself.
        if ($notices !== []) {
            session()->flash('cart_notices', $notices);
        }

        $this->redirectRoute('cart', [$locale], navigate: true);
    }

    private function isCancelled(): bool
    {
        return (bool) DB::transaction(function (): bool {
            $order = Order::lockForUpdate()->find($this->orderId);

            return $order === null || $order->status === OrderStatus::Cancelled;
        });
    }

    public function render(): View
    {
        return view('livewire.shop.payment-failed', [
            'order' => $this->order(),
            'locale' => app()->getLocale(),
        ]);
    }
}
