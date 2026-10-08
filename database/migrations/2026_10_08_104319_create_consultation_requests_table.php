<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mobile');
            $table->string('project_type'); // ConsultationProjectType
            $table->string('area_range');    // ConsultationAreaRange
            $table->text('notes')->nullable();
            $table->string('locale')->default('ar'); // ar | en
            $table->string('ip')->nullable();
            $table->string('status')->default('new'); // LeadStatus
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
