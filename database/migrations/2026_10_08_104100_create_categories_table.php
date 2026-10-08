<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->unsignedTinyInteger('level')->default(1); // 1 root · 2 group · 3 leaf
            $table->json('name');
            $table->json('slug');
            // Per-locale generated columns for route binding + per-locale uniqueness.
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->json('description')->nullable(); // category-page hero copy
            $table->string('icon')->nullable();      // Lucide icon name (root, in mega menu)
            $table->json('buying_guide_title')->nullable();
            $table->boolean('show_on_home')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->unique('slug_ar');
            $table->unique('slug_en');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
