<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // brand name — not translated
            $table->string('slug')->unique();  // single slug, not per-locale
            $table->json('origin_country')->nullable();
            $table->json('description')->nullable();
            $table->json('feature_text')->nullable();
            $table->json('catalogue_label')->nullable();
            $table->boolean('is_featured')->default(false);   // one shown in the brands-page banner
            $table->boolean('is_authorised')->default(false); // "authorised distributor" badge
            $table->boolean('show_in_partners')->default(false);
            $table->boolean('show_on_home')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->index('is_featured');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
