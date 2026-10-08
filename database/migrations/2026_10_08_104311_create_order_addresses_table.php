<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete(); // 1:1
            $table->string('recipient_name');
            $table->string('mobile');
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->json('governorate_name'); // snapshot
            $table->string('city');
            $table->string('street');
            $table->string('floor_apartment')->nullable();
            $table->string('landmark')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
