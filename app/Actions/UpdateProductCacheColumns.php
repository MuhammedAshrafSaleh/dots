<?php

namespace App\Actions;

use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Settings;
use App\Support\ArabicNorm;
use Illuminate\Support\Collection;

/**
 * Recomputes a product's cached columns (min_price, max_compare_at,
 * stock_status, search_text) from its variants. Reads variants/brand with
 * fresh queries (never mutating the passed model's loaded relations) and
 * saves quietly so it never re-triggers the product observer.
 */
class UpdateProductCacheColumns
{
    public function __construct(private readonly Settings $settings) {}

    public function handle(Product $product): void
    {
        $variants = ProductVariant::query()->where('product_id', $product->id)->get();

        /** @var Collection<int, ProductVariant> $active */
        $active = $variants->where('is_active', true);

        $prices = $active->whereNotNull('price')->pluck('price');
        $compareAt = $active->whereNotNull('compare_at_price')->pluck('compare_at_price');

        $product->forceFill([
            'min_price' => $prices->isNotEmpty() ? $prices->min() : null,
            'max_compare_at' => $compareAt->isNotEmpty() ? $compareAt->max() : null,
            'stock_status' => $this->stockStatus($product, $active)->value,
            'search_text' => $this->searchText($product, $variants),
        ])->saveQuietly();
    }

    /** @param  Collection<int, ProductVariant>  $active */
    private function stockStatus(Product $product, Collection $active): StockStatus
    {
        if ($product->price_on_request || $active->isEmpty()) {
            return StockStatus::Order;
        }

        $globalThreshold = (int) $this->settings->get('commerce.low_stock_threshold', 5);
        $hasIn = false;
        $hasLow = false;

        foreach ($active as $variant) {
            $threshold = $variant->low_stock_threshold ?? $globalThreshold;

            if ($variant->stock_qty > $threshold) {
                $hasIn = true;
            } elseif ($variant->stock_qty > 0) {
                $hasLow = true;
            }
        }

        return match (true) {
            $hasIn => StockStatus::In,
            $hasLow => StockStatus::Low,
            default => StockStatus::Order,
        };
    }

    /** @param  Collection<int, ProductVariant>  $variants */
    private function searchText(Product $product, Collection $variants): string
    {
        // brand_id is a required FK, so there is always a brand to look up.
        $brandName = Brand::query()->whereKey($product->brand_id)->value('name');

        $raw = collect([
            $product->getTranslation('name', 'ar'),
            $product->getTranslation('name', 'en'),
            $product->sku,
            ...$variants->pluck('sku')->all(),
            $brandName,
        ])->filter()->map(fn ($v) => trim((string) $v))->filter()->unique()->implode(' ');

        return ArabicNorm::normalize($raw);
    }
}
