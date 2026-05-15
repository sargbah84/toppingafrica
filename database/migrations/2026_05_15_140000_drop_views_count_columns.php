<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the denormalized views_count columns on posts and creators. The
 * polymorphic `views` table is now the single source of truth for both;
 * every reader has been migrated to either Eloquent's withCount('views')
 * or a direct $model->views()->count() call.
 *
 * No backfill needed — the column was already in sync (verified against
 * production: posts.views_count sum = views table count = 11,685) and we
 * are not preserving its data anywhere outside the views table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->dropIndex(['views_count']);
            $table->dropColumn('views_count');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->unsignedInteger('views_count')->default(0);
        });

        Schema::table('creators', function (Blueprint $table): void {
            $table->unsignedBigInteger('views_count')->default(0)->after('follower_platform');
            $table->index('views_count');
        });

        // Repopulate from the views table so a rollback restores a working
        // ordered counter, not a column full of zeros.
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $postClass = addslashes(\App\Models\Post::class);
        $creatorClass = addslashes(\App\Models\Creator::class);

        if ($driver === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE posts
                SET views_count = COALESCE((
                    SELECT COUNT(*) FROM views
                    WHERE views.viewable_type = '{$postClass}'
                      AND views.viewable_id = posts.id
                ), 0)
            ");
            \Illuminate\Support\Facades\DB::statement("
                UPDATE creators
                SET views_count = COALESCE((
                    SELECT COUNT(*) FROM views
                    WHERE views.viewable_type = '{$creatorClass}'
                      AND views.viewable_id = creators.id
                ), 0)
            ");
        } else {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE posts
                LEFT JOIN (
                    SELECT viewable_id, COUNT(*) AS c FROM views
                    WHERE viewable_type = '{$postClass}'
                    GROUP BY viewable_id
                ) v ON v.viewable_id = posts.id
                SET posts.views_count = COALESCE(v.c, 0)
            ");
            \Illuminate\Support\Facades\DB::statement("
                UPDATE creators
                LEFT JOIN (
                    SELECT viewable_id, COUNT(*) AS c FROM views
                    WHERE viewable_type = '{$creatorClass}'
                    GROUP BY viewable_id
                ) v ON v.viewable_id = creators.id
                SET creators.views_count = COALESCE(v.c, 0)
            ");
        }
    }
};
