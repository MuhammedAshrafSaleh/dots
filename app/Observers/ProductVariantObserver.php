<?php

namespace App\Observers;

use App\Actions\UpdateProductCacheColumns;
use App\Models\ProductVariant;
use App\Support\CacheTags;

class ProductVariantObserver
{
    public function __construct(private readonly UpdateProductCacheColumns $updateCache) {}

    public function saved(ProductVariant $variant): void
    {
        $this->recache($variant);
    }

    public function deleted(ProductVariant $variant): void
    {
        $this->recache($variant);
    }

    private function recache(ProductVariant $variant): void
    {
        $variant->loadMissing('product');

        if ($variant->product !== null) {
            $this->updateCache->handle($variant->product);
        }

        CacheTags::flush(CacheTags::LISTINGS, CacheTags::FACETS);
    }
}
