<?php

namespace App\Livewire\Shop;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethodCode;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
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
 * Two things can make this page terminal out from under the customer:
 *  - the reservation sweeper can CANCEL the order in the seconds they spend
 *    choosing here ($expired); and
 *  - the payment can SETTLE (a webhook/return lands, or they opened this URL
 *    after already paying) — a paid/refunded order is terminal ($settled).
 * So every branch re-loads the order under a row lock and re-checks both before
 * mutating anything. A settled order shows a "done" message and a link to the
 * order, with no mutating controls — and never starts a new checkout intention.
 * A cancelled order offers a fresh cart from the order's items.
 */
class PaymentFailed extends Component
{
    public int $orderId;

    public string $option = 'retry';

    /** Set once the sweeper has cancelled the order out from under the page. */
    public bool $expired = false;

    /** Set once the payment has settled (paid/refunded) — terminal, no actions. */
    public bool $settled = false;

    public function mount(string $number): void
    {
        $order = Order::where('number', $number)->first();

        if ($order === null || ! $order->viewableInSession()) {
            throw new NotFoundHttpException;
        }

        $this->orderId = $order->id;
        $this->expired = $order->status === OrderStatus::Cancelled;
        $this->settled = $this->isSettled($order->payment_status);
    }

    /**
     * Terminal payment states: the money lifecycle is closed, so no action on
     * this page may mutate the order or start a checkout intention.
     *   paid     — money taken, order settled.
     *   refunded — money taken then returned; the record is closed.
     * pending / awaiting_transfer / failed remain actionable (retry or switch).
     */
    private function isSettled(OrderPaymentStatus $status): bool
    {
        return in_array($status, [OrderPaymentStatus::Paid, OrderPaymentStatus::Refunded], true);
    }

    /**
     * Re-read the order under a row lock and classify any terminal state:
     *   'settled' — payment closed (paid/refunded) — takes precedence, since a
     *               late success after an auto-cancel (paid + cancelled) means
     *               money was taken and the customer must see "done", not "lost".
     *   'expired' — the sweeper cancelled it (reservation released).
     *   null      — still actionable.
     */
    private function terminalState(): ?string
    {
        return DB::transaction(function (): ?string {
            $order = Order::lockForUpdate()->find($this->orderId);

            if ($order === null) {
                return 'expired';
            }

            if ($this->isSettled($order->payment_status)) {
                return 'settled';
            }

            return $order->status === OrderStatus::Cancelled ? 'expired' : null;
        });
    }

    private function markTerminal(string $state): void
    {
        if ($state === 'settled') {
            $this->settled = true;
        } else {
            $this->expired = true;
        }
    }

    private function order(): Order
    {
        return Order::with(['items', 'paymentMethod'])->findOrFail($this->orderId);
    }

    public function proceed(): void
    {
        // Locked re-read before any branch acts. Covers the retry branch (which
        // starts a checkout intention) and short-circuits a page that was opened
        // before the order was cancelled or settled.
        if (($state = $this->terminalState()) !== null) {
            $this->markTerminal($state);

            return;
        }

        if ($this->option === 'retry') {
            try {
                // createCheckout refreshes reserved_until, so retrying can never
                // be swept underneath the customer.
                $url = app(PaymentGateway::class)->createCheckout($this->order());
            } catch (PaymentException $e) {
                $this->dispatch('toast', tone: 'error', title: __('ui.pf_gateway_error'));

                return;
            }

            $this->redirect($url);

            return;
        }

        $code = $this->option === 'cod' ? PaymentMethodCode::Cod : PaymentMethodCode::InstapayWhatsapp;
        $method = PaymentMethod::active()->where('code', $code->value)->first();

        if ($method === null) {
            $this->dispatch('toast', tone: 'error', title: __('ui.pf_retry_soon'));

            return;
        }

        $instapay = $code === PaymentMethodCode::InstapayWhatsapp;
        $terminal = null;

        DB::transaction(function () use ($method, $code, $instapay, &$terminal): void {
            $order = Order::lockForUpdate()->find($this->orderId);

            // Re-check under the lock: between the guard above and here the order
            // may have been settled (a webhook landed) or cancelled (the
            // sweeper). Either way, mutate nothing.
            if ($order === null) {
                $terminal = 'expired';

                return;
            }

            if ($this->isSettled($order->payment_status)) {
                $terminal = 'settled';

                return;
            }

            if ($order->status === OrderStatus::Cancelled) {
                $terminal = 'expired';

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

        if ($terminal !== null) {
            $this->markTerminal($terminal);

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

    /**
     * Map the latest failed payment's `failure_code` to a bilingual lang key.
     * Returns null when there is no failed payment (e.g. gateway threw before
     * recording anything). Raw `failure_message` is never returned or rendered.
     */
    private function failureReasonKey(): ?string
    {
        // ISO 8583 numeric codes and Paymob string codes → curated key.
        static $map = [
            '51' => 'pf_reason_insufficient_funds', // insufficient funds
            '61' => 'pf_reason_insufficient_funds', // exceeds withdrawal amount limit
            '65' => 'pf_reason_insufficient_funds', // exceeds withdrawal frequency limit
            'INSUFFICIENT_FUNDS' => 'pf_reason_insufficient_funds',
            '54' => 'pf_reason_expired_card',        // expired card
            'EXPIRED_CARD' => 'pf_reason_expired_card',
            'CARD_EXPIRED' => 'pf_reason_expired_card',
            '3DS_FAILED' => 'pf_reason_3ds_failed',
            'AUTHENTICATION_FAILED' => 'pf_reason_3ds_failed',
            'A5' => 'pf_reason_3ds_failed',
            'AUTH_FAILED' => 'pf_reason_auth_error',
            'AUTHORIZATION_FAILED' => 'pf_reason_auth_error',
            '05' => 'pf_reason_declined',            // do not honor
            '14' => 'pf_reason_declined',            // invalid card number
            '15' => 'pf_reason_declined',            // no such issuer
            '57' => 'pf_reason_declined',            // transaction not permitted
            '62' => 'pf_reason_declined',            // restricted card
            'DECLINED' => 'pf_reason_declined',
        ];

        $payment = Payment::where('order_id', $this->orderId)
            ->where('status', PaymentStatus::Failed)
            ->latest()
            ->first();

        if ($payment === null) {
            return null;
        }

        return $map[$payment->failure_code] ?? 'pf_reason_generic';
    }

    public function render(): View
    {
        return view('livewire.shop.payment-failed', [
            'order' => $this->order(),
            'locale' => app()->getLocale(),
            'reasonKey' => ($this->expired || $this->settled) ? null : $this->failureReasonKey(),
        ]);
    }
}
