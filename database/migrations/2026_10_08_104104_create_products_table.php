<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete(); // leaf (level 3)
            $table->json('name');
            $table->json('slug');
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->string('sku')->unique(); // primary code
            $table->json('overview')->nullable();          // rich HTML (sanitized on save), translatable
            $table->json('variants_summary')->nullable();  // e.g. "3 finishes · 2 tones" (auto if empty)
            $table->json('warranty')->nullable();
            $table->boolean('price_on_request')->default(false); // hides price + cart, WhatsApp primary
            $table->decimal('min_price', 12, 2)->nullable();       // cache from variants
            $table->decimal('max_compare_at', 12, 2)->nullable();  // cache for discount badge
            $table->string('stock_status')->default('order'); // in | low | order (StockStatus), cached
            $table->boolean('is_new')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_smart_pick')->default(false);
            $table->boolean('is_dimmable')->default(false);
            $table->boolean('ships_24h')->default(false);
            $table->integer('sort_weight')->default(0);
            $table->text('search_text')->nullable(); // name ar+en + SKUs + brand (FULLTEXT)
            $table->string('status')->default('draft'); // draft | active | archived (ProductStatus)
            $table->timestamp('published_at')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->unique('slug_ar');
            $table->unique('slug_en');
            $table->index(['status', 'category_id']);
            $table->index('min_price');
            $table->index('stock_status');
            $table->index('sort_weight');
        });

        // FULLTEXT with the ngram parser handles short Arabic/SKU tokens the
        // default parser drops. MySQL-only; the suite runs on MySQL from Phase 2.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT products_search_text_fulltext (search_text) WITH PARSER ngram');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
