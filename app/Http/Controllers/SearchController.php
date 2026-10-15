<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(string $locale): View
    {
        return view('site.search', compact('locale'));
    }
}
