<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\PageSection;
use Illuminate\View\View;

class BrandsController extends Controller
{
    public function __invoke(string $locale): View
    {
        $q = request()->string('q')->trim()->toString();
        $letter = request()->string('letter')->trim()->toString();

        $sections = PageSection::where('page', 'brands')->get()->keyBy('key');

        $featured = Brand::active()->where('is_featured', true)->first();

        $brands = Brand::active()
            ->when($q, fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->when($letter && $letter !== 'all', fn ($query) => $query->where('name', 'like', $letter.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // A–Z letters that actually have brands (always computed from all active brands,
        // so the letter bar does not disappear when a filter is applied).
        $allLetters = Brand::active()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
            ->unique()
            ->values();

        return view('site.brands', compact(
            'sections', 'featured', 'brands', 'allLetters', 'q', 'letter', 'locale'
        ));
    }
}
