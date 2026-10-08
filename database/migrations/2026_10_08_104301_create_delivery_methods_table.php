<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fully admin-managed; no hardcoded delivery methods in code.
        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('note')->nullable(); // optional free line
            $table->string('icon')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('price_is_from')->default(false); // shows "from"
            $table->decimal('free_over_amount', 12, 2)->nullable(); // null = never free
            $table->boolean('requires_address')->default(true); // false = showroom pickup
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_methods');
    }
};
