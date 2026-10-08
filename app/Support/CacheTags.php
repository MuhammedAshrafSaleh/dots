<?php

namespace App\Support;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

/**
 * Central list of cache tags and a driver-safe flush. Tagged cache only works
 * on taggable stores (redis, memcached, array); on database/file — the allowed
 * fallback — we flush the whole store instead of failing.
 */
final class CacheTags
{
    public const CATEGORIES = 'categories'; // category tree + counts

    public const BRANDS = 'brands';         // brand lists + counts

    public const FACETS = 'facets';         // filter facet counts

    public const LISTINGS = 'listings';     // filtered product listing pages

    public const SECTIONS = 'sections';     // page_sections

    public const SETTINGS = 'settings';     // settings values

    public static function flush(string ...$tags): void
    {
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags($tags)->flush();

            return;
        }

        Cache::flush();
    }
}
