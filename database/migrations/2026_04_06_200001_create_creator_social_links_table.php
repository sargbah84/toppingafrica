<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_social_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // youtube, instagram, tiktok, twitter, facebook, website
            $table->string('url');
            $table->string('handle')->nullable();
            $table->timestamps();

            $table->index('creator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_social_links');
    }
};
