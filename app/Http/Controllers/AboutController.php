<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\PageSection;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        $sections = PageSection::where('page', 'about')
            ->with('media')
            ->get()
            ->keyBy('key');

        $partners = Brand::active()
            ->where('show_in_partners', true)
            ->with('media')
            ->orderBy('sort_order')
            ->get();

        return view('site.about', compact('sections', 'partners'));
    }
}
