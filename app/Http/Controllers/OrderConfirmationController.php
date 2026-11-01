<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Settings;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Order confirmation — reachable right after placing (order id held in the
 * session) or by the signed-in customer who owns it. Shows one of three
 * variants (COD / card paid / InstaPay awaiting transfer) from the order itself.
 */
class OrderConfirmationController extends Controller
{
    public function __invoke(string $locale, string $number): View
    {
        $order = Order::where('number', $number)
            ->with(['items', 'address', 'paymentMethod'])
            ->first();

        if ($order === null || ! $order->viewableInSession()) {
            throw new NotFoundHttpException;
        }

        $instapayAddress = (string) (app(Settings::class)->get('commerce.instapay_address') ?? '');

        return view('site.order-confirmation', compact('order', 'locale', 'instapayAddress'));
    }
}
