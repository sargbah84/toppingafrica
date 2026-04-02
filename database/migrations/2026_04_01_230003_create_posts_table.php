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
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();

            // Post type: article, video, quiz, trivia, listicle, gallery
            $table->string('post_type', 30)->default('article');

            // Post type-specific data (quiz questions, video URL, etc.)
            $table->json('type_data')->nullable();

            // SEO fields
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->json('og_meta')->nullable();
            $table->json('social_sharing')->nullable();

            // Status & scheduling
            $table->string('status', 20)->default('draft'); // draft, published, scheduled, archived
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // Metrics
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedSmallInteger('reading_time')->default(0);
            $table->boolean('is_featured')->default(false);

            // AI generation tracking
            $table->string('ai_provider')->nullable();
            $table->json('generation_params')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('post_type');
            $table->index('published_at');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
