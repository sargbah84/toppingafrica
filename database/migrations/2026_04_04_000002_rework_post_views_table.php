<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->string('ip_hash', 64)->nullable()->after('user_id');
            $table->timestamp('viewed_at')->nullable()->after('referer');

            $table->index('ip_hash');
            $table->index(['post_id', 'viewed_at']);
        });

        // Backfill ip_hash from existing ip_address data
        $appKey = config('app.key');
        \Illuminate\Support\Facades\DB::table('post_views')
            ->whereNotNull('ip_address')
            ->orderBy('id')
            ->chunk(500, function ($views) use ($appKey) {
                foreach ($views as $view) {
                    \Illuminate\Support\Facades\DB::table('post_views')
                        ->where('id', $view->id)
                        ->update([
                            'ip_hash' => hash('sha256', $view->ip_address . $appKey),
                            'viewed_at' => $view->created_at,
                        ]);
                }
            });

        Schema::table('post_views', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'referer']);
        });
    }

    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('user_id');
            $table->string('referer')->nullable()->after('user_agent');
        });

        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex(['ip_hash']);
            $table->dropIndex(['post_id', 'viewed_at']);
            $table->dropColumn(['ip_hash', 'viewed_at']);
        });
    }
};
