<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BrandDetailController extends Controller
{
    public function __invoke(string $locale, Brand $brand): View|Response
    {
        if (! $brand->is_active) {
            abort(404);
        }

        $catFilter = request()->string('cat')->trim()->toString();

        // Tabs: root-level categories that have at least one product for this brand.
        // "Root" = parent_id is null (level 0 in the seeder, but we use parent_id=null
        // as the reliable signal since level is not always set).
        $tabs = Category::whereNull('parent_id')
            ->whereHas('products', fn ($q) => $q->active()->where('brand_id', $brand->id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Product query: all active products for this brand, optionally filtered by root category.
        $products = Product::active()
            ->where('brand_id', $brand->id)
            ->when(
                $catFilter && $catFilter !== 'all',
                fn ($q) => $q->whereHas(
                    'category',
                    fn ($cq) => $cq->whereHas(
                        'parent',
                        fn ($pq) => $pq->where('id', (int) $catFilter)
                    )->orWhere('id', (int) $catFilter)
                )
            )
            ->with(['media', 'brand'])
            ->orderByDesc('sort_weight')
            ->paginate(8)
            ->withQueryString();

        // "Specified by Dots" projects: completed projects that reference this brand's products.
        $projects = Project::active()
            ->completed()
            ->whereHas('products', fn ($q) => $q->where('brand_id', $brand->id))
            ->with('media')
            ->orderByDesc('completed_year')
            ->take(3)
            ->get();

        return view('site.brand-detail', compact(
            'brand', 'tabs', 'catFilter', 'products', 'projects', 'locale'
        ));
    }
}
