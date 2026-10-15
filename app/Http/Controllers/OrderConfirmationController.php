<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Order confirmation — reachable right after placing (order id held in the
 * session) or by the signed-in customer who owns it. The full three-variant
 * confirmation screen (COD / card / InstaPay) is finished in T7.3.
 */
class OrderConfirmationController extends Controller
{
    public function __invoke(string $locale, string $number): View
    {
        $order = Order::where('number', $number)->with(['items', 'address'])->first();

        if ($order === null || ! $this->authorized($order)) {
            throw new NotFoundHttpException;
        }

        return view('site.order-confirmation', compact('order', 'locale'));
    }

    private function authorized(Order $order): bool
    {
        $placed = session('orders.placed', []);
        if (is_array($placed) && in_array($order->id, $placed, true)) {
            return true;
        }

        $customerId = auth('customer')->id();

        return $customerId !== null && $order->customer_id === $customerId;
    }
}
