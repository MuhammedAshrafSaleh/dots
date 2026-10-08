<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flat per-product spec rows: name + value, no groups.
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('name');  // e.g. "luminous flux"
            $table->json('value'); // e.g. "2,450 lm"
            $table->integer('sort_order')->default(0);

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specs');
    }
};
