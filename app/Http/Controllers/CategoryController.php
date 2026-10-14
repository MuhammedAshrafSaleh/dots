<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CacheTags;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __invoke(string $locale, Category $category): View|Response
    {
        if (! $category->is_active) {
            abort(404);
        }

        // Children with cached active product counts.
        $children = CacheTags::remember(
            CacheTags::CATEGORIES,
            "cat.{$category->id}.children",
            3600,
            fn () => Category::active()
                ->where('parent_id', $category->id)
                ->withCount(['products' => fn ($q) => $q->active()])
                ->orderBy('sort_order')
                ->get()
        );

        // Ids in scope: this category + its direct children.
        $scopeIds = $children->pluck('id')->prepend($category->id);

        // Total product count across this category and its children.
        $totalCount = CacheTags::remember(
            CacheTags::CATEGORIES,
            "cat.{$category->id}.total",
            3600,
            fn () => Product::active()->whereIn('category_id', $scopeIds->all())->count()
        );

        // Top picks: featured products configured in admin (up to 4).
        $featuredProducts = $category->featuredProducts()
            ->active()
            ->with(['media', 'brand'])
            ->take(4)
            ->get();

        // FAQs for the buying guide accordion.
        $faqs = $category->faqs;

        // Brand chips: top brands by product count in this category scope.
        $brands = CacheTags::remember(
            CacheTags::CATEGORIES,
            "cat.{$category->id}.brands",
            3600,
            function () use ($scopeIds) {
                return Brand::active()
                    ->withCount(['products' => fn ($q) => $q->active()->whereIn('category_id', $scopeIds)])
                    ->having('products_count', '>', 0)
                    ->orderByDesc('products_count')
                    ->take(8)
                    ->get();
            }
        );

        return view('site.category', compact(
            'category', 'children', 'totalCount', 'featuredProducts', 'faqs', 'brands', 'locale'
        ));
    }
}
