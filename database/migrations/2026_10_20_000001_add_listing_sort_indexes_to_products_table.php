<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes that let the two most-hit, uncached listing sorts
 * ("recommended" and "newest") be served straight from an index, removing
 * the filesort over the whole active catalogue (see T9.1 report in PROGRESS.md
 * for before/after timings on the 10k-product LargeCatalogSeeder).
 *
 * Descending column order is required to match the ORDER BY, so these use a raw
 * ALTER (the schema builder cannot express per-column direction). MySQL-only,
 * like the FULLTEXT index on this table; the suite runs on MySQL from Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // "recommended" sort: WHERE status=active ORDER BY sort_weight DESC, is_best_seller DESC, id ASC
        DB::statement('ALTER TABLE products ADD INDEX products_recommended_sort_index (status, sort_weight DESC, is_best_seller DESC, id)');

        // "newest" sort: WHERE status=active ORDER BY published_at DESC, id DESC
        DB::statement('ALTER TABLE products ADD INDEX products_published_sort_index (status, published_at DESC, id DESC)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('products', function ($table): void {
            $table->dropIndex('products_recommended_sort_index');
            $table->dropIndex('products_published_sort_index');
        });
    }
};
