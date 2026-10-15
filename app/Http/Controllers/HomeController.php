<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Service;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sections = PageSection::where('page', 'home')
            ->with('media')
            ->get()
            ->keyBy('key');

        $categories = Category::active()
            ->where('show_on_home', true)
            ->with('media')
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        $cardWith = ['media', 'brand', 'variants' => fn ($q) => $q->active()->with('swatchAttributeValues')];

        $newProducts = Product::active()
            ->where('is_new', true)
            ->with($cardWith)
            ->orderByDesc('sort_weight')
            ->limit(4)
            ->get();

        $bestProducts = Product::active()
            ->where('is_best_seller', true)
            ->with($cardWith)
            ->orderByDesc('sort_weight')
            ->limit(4)
            ->get();

        $smartProducts = Product::active()
            ->where('is_smart_pick', true)
            ->with($cardWith)
            ->orderByDesc('sort_weight')
            ->limit(4)
            ->get();

        $services = Service::active()
            ->with('media')
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::active()
            ->where('show_on_home', true)
            ->with('media')
            ->orderBy('sort_order')
            ->get();

        return view('site.home', compact(
            'sections',
            'categories',
            'newProducts',
            'bestProducts',
            'smartProducts',
            'services',
            'brands',
        ));
    }
}
