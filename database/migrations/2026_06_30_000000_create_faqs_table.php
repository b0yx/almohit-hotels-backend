<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->morphs('faqable');
            $table->string('question');
            $table->text('answer');
            $table->string('question_ar')->nullable();
            $table->text('answer_ar')->nullable();
            $table->string('slug')->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 180)->nullable();
            $table->string('meta_title_ar', 70)->nullable();
            $table->string('meta_description_ar', 180)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['faqable_type', 'faqable_id', 'is_active', 'sort_order'], 'idx_faqs_lookup');
            $table->index(['faqable_type', 'faqable_id', 'slug'], 'idx_faqs_parent_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
