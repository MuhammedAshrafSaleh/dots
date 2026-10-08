<?php

namespace App\Observers;

use App\Models\Category;
use App\Support\CacheTags;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        $this->flush();
    }

    public function deleted(Category $category): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        CacheTags::flush(CacheTags::CATEGORIES, CacheTags::LISTINGS, CacheTags::FACETS);
    }
}
