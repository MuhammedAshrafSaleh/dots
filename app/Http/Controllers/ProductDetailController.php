<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    public function __invoke(string $locale, Product $product): View|Response
    {
        if ($product->status !== ProductStatus::Active || $product->published_at > now()) {
            abort(404);
        }

        $product->load([
            'brand',
            'category.parent',
            'specs',
            'media',
            'relatedProducts' => fn ($q) => $q->active()
                ->with(['media', 'brand', 'variants' => fn ($v) => $v->active()->with('swatchAttributeValues')])
                ->take(4),
        ]);

        $gallery = $product->getMedia('gallery');
        $files = $product->getMedia('files');

        $deliveryPage = Page::active()->where('key', 'delivery')->first();
        $returnsPage = Page::active()->where('key', 'returns')->first();

        $deliveryHtml = $deliveryPage?->getTranslation('body', $locale) ?? '';
        $returnsHtml = $returnsPage?->getTranslation('body', $locale) ?? '';

        $overview = $product->getTranslation('overview', $locale);
        $specs = $product->specs;

        return view('site.product-detail', compact(
            'product',
            'gallery',
            'files',
            'specs',
            'overview',
            'deliveryHtml',
            'returnsHtml',
            'locale',
        ));
    }
}
