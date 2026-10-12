<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_requests', function (Blueprint $table) {
            // Sponsored articles: the buyer's own article and the text their link sits on.
            $table->longText('article_body')->nullable()->after('description');
            $table->string('anchor_text', 120)->nullable()->after('primary_url');
        });

        // Like "creator", "contributor" has no permissions — it marks a
        // frontend account that has submitted a sponsored article.
        Role::query()->firstOrCreate(['name' => 'contributor', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Role::query()->where('name', 'contributor')->where('guard_name', 'web')->delete();

        Schema::table('promotion_requests', function (Blueprint $table) {
            $table->dropColumn(['article_body', 'anchor_text']);
        });
    }
};
