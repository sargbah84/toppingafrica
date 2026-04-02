<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->string('grade', 5)->nullable();
            $table->unsignedTinyInteger('content_quality_score')->default(0);
            $table->unsignedTinyInteger('technical_seo_score')->default(0);
            $table->unsignedTinyInteger('readability_score')->default(0);
            $table->unsignedTinyInteger('user_engagement_score')->default(0);
            $table->unsignedTinyInteger('on_page_elements_score')->default(0);
            $table->json('content_quality_details')->nullable();
            $table->json('technical_seo_details')->nullable();
            $table->json('readability_details')->nullable();
            $table->json('user_engagement_details')->nullable();
            $table->json('on_page_elements_details')->nullable();
            $table->json('strengths')->nullable();
            $table->json('improvements')->nullable();
            $table->json('critical_issues')->nullable();
            $table->json('keyword_suggestions')->nullable();
            $table->json('content_recommendations')->nullable();
            $table->json('google_trends_data')->nullable();
            $table->json('applied_recommendations')->nullable();
            $table->unsignedInteger('word_count_at_analysis')->nullable();
            $table->string('focus_keyword_at_analysis')->nullable();
            $table->timestamps();

            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_analyses');
    }
};
