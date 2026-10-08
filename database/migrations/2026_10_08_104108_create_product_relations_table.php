<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_relations', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            $table->string('type'); // together | cart_suggestion (ProductRelationType)
            $table->integer('sort_order')->default(0);

            $table->primary(['product_id', 'related_id', 'type']);
            $table->index('related_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relations');
    }
};
