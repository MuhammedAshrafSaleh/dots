<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Empty pivot for a method = available in all governorates.
        Schema::create('delivery_method_governorate', function (Blueprint $table) {
            $table->foreignId('delivery_method_id')->constrained()->cascadeOnDelete();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();

            $table->primary(['delivery_method_id', 'governorate_id']);
            $table->index('governorate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_method_governorate');
    }
};
