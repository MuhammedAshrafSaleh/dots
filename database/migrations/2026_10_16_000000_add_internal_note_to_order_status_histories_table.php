<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            // Admin-only note (single language, never shown to the customer).
            // The translatable `note` column stays customer-visible.
            $table->text('internal_note')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropColumn('internal_note');
        });
    }
};
