<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->constrained()->restrictOnDelete();
            $table->json('title');
            $table->json('slug');
            $table->string('slug_ar')->storedAs("json_unquote(json_extract(`slug`, '$.ar'))");
            $table->string('slug_en')->storedAs("json_unquote(json_extract(`slug`, '$.en'))");
            $table->json('excerpt')->nullable();
            $table->json('body')->nullable();        // rich HTML (sanitized)
            $table->json('author_name')->nullable(); // default: DOTS editorial team
            $table->json('author_role')->nullable();
            $table->unsignedTinyInteger('read_minutes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('draft'); // draft | published | scheduled (PostStatus)
            $table->timestamp('published_at')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->unique('slug_ar');
            $table->unique('slug_en');
            $table->index(['status', 'published_at']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
