<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            // Rough AI-generated estimate of the creator's largest-platform
            // follower count. Nullable because not every creator will have
            // a confident estimate and we'd rather store null than fake data.
            $table->unsignedBigInteger('follower_count')->nullable()->after('is_featured');
            // Which platform the follower_count above is for (e.g. "instagram",
            // "tiktok"). Helps the UI show "500K on TikTok" instead of a naked number.
            $table->string('follower_platform', 32)->nullable()->after('follower_count');

            $table->index('follower_count');
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->dropIndex(['follower_count']);
            $table->dropColumn(['follower_count', 'follower_platform']);
        });
    }
};
