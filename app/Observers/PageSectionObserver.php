<?php

namespace App\Observers;

use App\Models\PageSection;
use App\Support\CacheTags;

class PageSectionObserver
{
    public function saved(PageSection $section): void
    {
        CacheTags::flush(CacheTags::SECTIONS);
    }

    public function deleted(PageSection $section): void
    {
        CacheTags::flush(CacheTags::SECTIONS);
    }
}
