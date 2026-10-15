<?php

namespace App\Http\Controllers;

use App\Enums\OrderPaymentStatus;
use App\Models\Order;
use App\Services\Payments\PaymobGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Browser redirect after the customer leaves Unified Checkout. The webhook is
 * the source of truth, but we verify the callback HMAC and record it here too
 * so the confirmation page reflects the result even if the webhook is slow.
 * Then we send the customer to confirmation (paid) or payment-failed.
 */
class PaymobReturnController extends Controller
{
    public function __invoke(Request $request, string $locale, PaymobGateway $gateway): RedirectResponse
    {
        $obj = $gateway->verifyCallback($request->query());

        $merchantRef = $request->query('merchant_order_id');
        $order = is_string($merchantRef) ? Order::where('number', $merchantRef)->first() : null;

        if ($obj !== null) {
            $gateway->recordTransaction($obj, hmacVerified: true);
            $order?->refresh();
        }

        if ($order === null) {
            return redirect()->route('cart', $locale);
        }

        $route = $order->payment_status === OrderPaymentStatus::Paid
            ? 'order.confirmation'
            : 'order.payment-failed';

        return redirect()->route($route, [$locale, $order->number]);
    }
}
