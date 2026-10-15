<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CartController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('site.cart', compact('locale'));
    }
}
