<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            // Slug of the creator Perplexity proposes for a 'spotlight' idea.
            // Stored as a string (not FK) so historical ideas survive creator
            // renames/deletes. Resolved to a Creator at generation time.
            $table->string('suggested_creator_slug')->nullable()->after('suggested_post_type');
        });
    }

    public function down(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            $table->dropColumn('suggested_creator_slug');
        });
    }
};
