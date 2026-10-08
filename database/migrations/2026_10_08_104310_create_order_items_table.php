<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product/variant/price all snapshotted onto the line.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); // null if product deleted
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->json('product_name');   // snapshot
            $table->json('variant_label')->nullable(); // snapshot: matte black · 3000K
            $table->string('sku');          // snapshot
            $table->string('image_url')->nullable(); // snapshot
            $table->decimal('unit_price', 12, 2);
            $table->integer('qty');
            $table->decimal('line_total', 12, 2);

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
