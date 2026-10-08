<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('number'); // 01
            $table->json('title');
            $table->json('slug');
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->json('excerpt')->nullable();        // card description
            $table->json('description')->nullable();     // service-page header copy
            $table->json('included_title')->nullable();
            $table->json('duration_text')->nullable();
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
        Schema::dropIfExists('services');
    }
};
