<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic: attached to a category or service, or null = the general
        // services-page FAQ.
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('faqable_type')->nullable();
            $table->unsignedBigInteger('faqable_id')->nullable();
            $table->json('question');
            $table->json('answer');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['faqable_type', 'faqable_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
