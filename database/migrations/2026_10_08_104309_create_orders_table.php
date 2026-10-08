<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // prefix + id, generated once at creation; also the tracking number.
            $table->string('number')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // null = guest
            $table->string('contact_name');
            $table->string('contact_mobile');
            $table->string('contact_email')->nullable();
            $table->boolean('is_company')->default(false);
            $table->string('company_tax_id')->nullable();
            $table->string('locale')->default('ar'); // ar | en
            $table->string('status')->default('new');            // OrderStatus
            $table->string('payment_status')->default('pending'); // OrderPaymentStatus
            $table->foreignId('payment_method_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('payment_method_code')->nullable(); // snapshot
            $table->foreignId('delivery_method_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('delivery_method_name')->nullable();  // snapshot
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable(); // snapshot
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('vat_rate', 12, 2)->default(14);   // snapshot
            $table->decimal('vat_amount', 12, 2)->default(0);  // included in total
            $table->date('expected_delivery_at')->nullable();
            $table->timestamp('reserved_until')->nullable(); // 24h hold for InstaPay
            $table->text('admin_notes')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('contact_mobile');
            $table->index(['status', 'placed_at']);
            $table->index('payment_status');
        });

        // Order number = prefix + id; sequence starts at 10001.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders AUTO_INCREMENT = 10001');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
