<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every WhatsApp click, even if the message is never sent.
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // QuoteSource
            $table->string('sourceable_type')->nullable();
            $table->unsignedBigInteger('sourceable_id')->nullable();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('qty')->nullable();
            $table->json('cart_snapshot')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->string('page_url')->nullable();
            $table->string('locale')->default('ar'); // ar | en
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('status')->default('new'); // LeadStatus
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('source');
            $table->index(['sourceable_type', 'sourceable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
