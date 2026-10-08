<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // delivery · returns · warranty · careers · terms · privacy
            $table->json('title');
            $table->json('slug');
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->json('lede')->nullable();
            $table->json('body')->nullable(); // rich HTML (sanitized)
            $table->boolean('show_in_footer')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->unique('slug_ar');
            $table->unique('slug_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
