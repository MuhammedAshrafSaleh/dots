<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // cct · wattage · size · finish
            $table->json('name');
            $table->string('display')->default('chip'); // chip | swatch | button (AttributeDisplay)
            $table->string('unit')->nullable();          // K · W · mm
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_variant_axis')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
