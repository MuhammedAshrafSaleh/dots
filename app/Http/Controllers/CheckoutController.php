<?php

namespace App\Http\Controllers;

use App\Services\CartManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __invoke(string $locale, CartManager $cart): View|RedirectResponse
    {
        if ($cart->items()->isEmpty()) {
            return redirect()->route('cart', $locale);
        }

        return view('site.checkout', compact('locale'));
    }
}
