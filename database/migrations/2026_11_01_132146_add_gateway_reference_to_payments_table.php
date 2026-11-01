<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // The unique special_reference we send on each Paymob intention
            // (`{order.number}-{token}`). Paymob echoes it back verbatim as
            // merchant_order_id, so a transaction is resolved to its order by an
            // exact lookup on this column — no parsing of the order number.
            $table->string('gateway_reference')->nullable()->after('gateway_ref');
            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_reference']);
            $table->dropColumn('gateway_reference');
        });
    }
};
