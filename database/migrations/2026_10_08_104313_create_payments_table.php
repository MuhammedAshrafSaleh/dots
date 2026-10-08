<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // paymob
            $table->string('gateway_ref')->nullable(); // intention / order id
            $table->string('transaction_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('EGP');
            $table->string('status')->default('pending'); // PaymentStatus
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->json('raw')->nullable();
            $table->boolean('hmac_verified')->default(false);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
