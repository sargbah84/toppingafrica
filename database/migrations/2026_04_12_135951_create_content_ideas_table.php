<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('niche');
            $table->string('category')->nullable();
            $table->string('suggested_keyword')->nullable();
            $table->unsignedTinyInteger('seo_score')->default(0);
            $table->string('search_interest')->default('medium');
            $table->string('competition')->default('medium');
            $table->unsignedTinyInteger('relevance_score')->default(5);
            $table->string('source')->default('perplexity');
            $table->string('source_url')->nullable();
            $table->string('suggested_tone')->default('professional');
            $table->string('suggested_length')->default('long');
            $table->string('suggested_post_type')->default('article');
            $table->string('status')->default('pending');
            $table->foreignId('generated_post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('researched_at');
            $table->timestamps();

            $table->index(['status', 'seo_score']);
            $table->index(['niche', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_ideas');
    }
};
