<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            // Stamped when the order is cancelled (auto-sweep or admin): the
            // redemption no longer counts toward the once-per-customer gate and
            // its used_count is given back. The row is kept (not deleted) to
            // preserve the history of a coupon used on a later-cancelled order.
            $table->timestamp('released_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropColumn('released_at');
        });
    }
};
