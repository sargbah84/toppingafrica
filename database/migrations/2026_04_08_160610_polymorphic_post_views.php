<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic refactor of post_views → views.
 *
 * Generalizes the view-tracking table so any model can be tracked, not just
 * App\Models\Post. Adds (viewable_type, viewable_id), backfills existing rows
 * with viewable_type='App\Models\Post' / viewable_id=post_id, drops the old
 * post_id column and its FK + composite indexes, and renames the table to `views`.
 *
 * Production has ~3376 rows in this table, so the backfill is fast.
 *
 * The down() migration restores post_views with post_id but is destructive
 * for any rows whose viewable_type is not Post — those rows are dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Add the new polymorphic columns as nullable so we can backfill.
        Schema::table('post_views', function (Blueprint $table): void {
            $table->string('viewable_type', 191)->nullable()->after('id');
            $table->unsignedBigInteger('viewable_id')->nullable()->after('viewable_type');
        });

        // 2) Backfill from post_id. One statement, no row-by-row iteration.
        DB::table('post_views')
            ->whereNotNull('post_id')
            ->update([
                'viewable_type' => \App\Models\Post::class,
                'viewable_id' => DB::raw('post_id'),
            ]);

        // 3) Drop the FK and the two composite indexes that reference post_id,
        //    then drop the column itself. The order matters — the FK must go
        //    before the column, and the composite indexes are inferred by
        //    Laravel's dropForeign / dropIndex helpers via convention.
        Schema::table('post_views', function (Blueprint $table): void {
            $table->dropForeign('post_views_post_id_foreign');
            $table->dropIndex('post_views_post_id_created_at_index');
            $table->dropIndex('post_views_post_id_viewed_at_index');
            $table->dropColumn('post_id');
        });

        // 4) Now make the polymorphic columns NOT NULL and add a composite
        //    index. Doing this in a separate statement avoids "cannot change
        //    nullability while still nullable" weirdness on some MySQL versions.
        Schema::table('post_views', function (Blueprint $table): void {
            $table->string('viewable_type', 191)->nullable(false)->change();
            $table->unsignedBigInteger('viewable_id')->nullable(false)->change();
            $table->index(['viewable_type', 'viewable_id'], 'views_viewable_index');
            $table->index(['viewable_type', 'viewable_id', 'viewed_at'], 'views_viewable_viewed_at_index');
        });

        // 5) Rename the table.
        Schema::rename('post_views', 'views');
    }

    public function down(): void
    {
        // Reverse the rename first.
        Schema::rename('views', 'post_views');

        Schema::table('post_views', function (Blueprint $table): void {
            $table->dropIndex('views_viewable_index');
            $table->dropIndex('views_viewable_viewed_at_index');
            $table->unsignedBigInteger('post_id')->nullable()->after('id');
        });

        // Restore post_id only for rows that were originally Post views.
        // Any creator-view rows added after the up() migration will be
        // orphaned by this and deleted in the next step. This is destructive
        // by design — rolling back means accepting that creator views are lost.
        DB::table('post_views')
            ->where('viewable_type', \App\Models\Post::class)
            ->update(['post_id' => DB::raw('viewable_id')]);

        DB::table('post_views')
            ->where('viewable_type', '!=', \App\Models\Post::class)
            ->orWhereNull('post_id')
            ->delete();

        Schema::table('post_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
            $table->index(['post_id', 'created_at'], 'post_views_post_id_created_at_index');
            $table->index(['post_id', 'viewed_at'], 'post_views_post_id_viewed_at_index');
            $table->dropColumn(['viewable_type', 'viewable_id']);
        });
    }
};
