<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->string('status')->default('pending'); // pending | processing | completed | failed
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('variants_count')->default(0);
            $table->unsignedInteger('specs_count')->default(0);
            $table->unsignedInteger('images_count')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->unsignedInteger('warnings_count')->default(0);
            $table->string('report_path')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
