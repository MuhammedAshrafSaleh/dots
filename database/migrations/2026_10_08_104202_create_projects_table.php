<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('current'); // current | completed (ProjectStatus)
            $table->string('type');                        // ProjectType
            $table->json('title');
            $table->json('slug');
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->json('location')->nullable();
            $table->json('client')->nullable(); // null = private client
            $table->date('start_date')->nullable();
            $table->date('expected_completion')->nullable();
            $table->integer('completed_year')->nullable();
            $table->unsignedTinyInteger('progress')->default(0); // 0–100
            $table->string('stage')->nullable();                 // ProjectStage (current projects)
            $table->json('phase_label')->nullable();
            $table->json('scope_summary')->nullable();
            $table->json('about')->nullable();     // rich HTML (sanitized)
            $table->json('challenge')->nullable(); // completed projects
            $table->json('solution')->nullable();
            $table->json('result')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->unique('slug_ar');
            $table->unique('slug_en');
            $table->index(['status', 'is_active', 'sort_order']);
            $table->index('type');
            $table->index('completed_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
