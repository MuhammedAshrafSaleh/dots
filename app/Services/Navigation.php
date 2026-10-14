<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Builds the data the global layout needs: the category mega-menu tree
 * (root → group → leaf) and the footer link columns. Content is read from
 * the DB (categories with show_in_footer flags, active pages); caching is a
 * Phase 9 concern — the queries here are small and eager-loaded.
 */
class Navigation
{
    /**
     * Active root categories with their groups and leaves, for the mega menu.
     *
     * @return Collection<int, Category>
     */
    public function megaTree(): Collection
    {
        return Category::query()
            ->active()
            ->where('level', 1)
            ->with(['children' => fn ($q) => $q->active(), 'children.children' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Root categories flagged for the footer "Shop" column.
     *
     * @return Collection<int, Category>
     */
    public function footerCategories(): Collection
    {
        return Category::query()
            ->active()
            ->where('level', 1)
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Content pages flagged for the footer (help column).
     *
     * @return Collection<int, Page>
     */
    public function footerPages(): Collection
    {
        return Page::query()
            ->active()
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->get();
    }
}
