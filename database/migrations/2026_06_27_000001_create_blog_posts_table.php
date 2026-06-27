<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->longText('content');
            $table->string('featured_image');
            $table->string('featured_image_alt');
            $table->string('meta_title');
            $table->string('meta_description', 320);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('locale', 5)->default('en')->index();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('blog_categories')->restrictOnDelete();
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at'], 'idx_blog_posts_public');
            $table->index(['category_id', 'status'], 'idx_blog_posts_category_status');
            $table->index(['hotel_id', 'status'], 'idx_blog_posts_hotel_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
