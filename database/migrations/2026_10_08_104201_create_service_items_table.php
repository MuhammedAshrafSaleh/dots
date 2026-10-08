<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "What's included" bullet rows for a service.
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->json('text');
            $table->integer('sort_order')->default(0);

            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_items');
    }
};
