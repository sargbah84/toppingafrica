<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalized view counter on creators, mirroring posts.views_count.
 * Incremented by Creator::incrementViewsCount() when ViewTracker records
 * a new (deduplicated) view of a creator profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->unsignedBigInteger('views_count')->default(0)->after('follower_platform');
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->dropIndex(['views_count']);
            $table->dropColumn('views_count');
        });
    }
};
