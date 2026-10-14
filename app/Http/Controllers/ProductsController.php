<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ProductsController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('site.products', compact('locale'));
    }
}
