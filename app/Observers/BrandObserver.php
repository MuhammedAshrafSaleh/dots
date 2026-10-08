<?php

namespace App\Observers;

use App\Models\Brand;
use App\Support\CacheTags;

class BrandObserver
{
    public function saved(Brand $brand): void
    {
        $this->flush();
    }

    public function deleted(Brand $brand): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        CacheTags::flush(CacheTags::BRANDS, CacheTags::LISTINGS, CacheTags::FACETS);
    }
}
