<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique('slug_ar');
            $table->unique('slug_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_categories');
    }
};
