<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('home'); // home | office | other (AddressLabel)
            $table->string('recipient_name');
            $table->string('mobile');
            $table->foreignId('governorate_id')->constrained()->restrictOnDelete();
            $table->string('city');
            $table->string('street');
            $table->string('floor_apartment')->nullable();
            $table->string('landmark')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
