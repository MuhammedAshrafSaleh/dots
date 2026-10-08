<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editable content blocks for the static pages; fields follow a fixed
        // per-section schema in config/page_sections.php.
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page'); // home · about · services · brands · projects · blog · global
            $table->string('key');  // hero · stats · why · quote_band …
            $table->json('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['page', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
