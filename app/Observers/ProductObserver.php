<?php

namespace App\Observers;

use App\Actions\UpdateProductCacheColumns;
use App\Models\Product;
use App\Support\CacheTags;

class ProductObserver
{
    public function __construct(private readonly UpdateProductCacheColumns $updateCache) {}

    public function saved(Product $product): void
    {
        // Refresh search_text / stock_status when the product itself changes
        // (name, sku, brand, price_on_request). Guarded against recursion: the
        // action saves quietly.
        if ($product->wasChanged() || $product->wasRecentlyCreated) {
            $this->updateCache->handle($product);
        }

        $this->flush();
    }

    public function deleted(Product $product): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        CacheTags::flush(CacheTags::LISTINGS, CacheTags::FACETS, CacheTags::CATEGORIES, CacheTags::BRANDS);
    }
}
