<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creators', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio');
            $table->string('country');
            $table->string('category');
            $table->string('contact_email')->nullable();
            $table->string('status')->default('pending'); // pending, published, claimed
            $table->string('profile_image_url')->nullable();
            $table->string('profile_image_attribution')->nullable();
            $table->string('profile_image_license')->nullable();
            $table->string('claim_token')->nullable()->unique();
            $table->timestamp('claim_token_expires_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->string('claimed_by_email')->nullable();
            $table->boolean('pending_claim_edit')->default(false);
            $table->boolean('is_rising')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creators');
    }
};
