<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_social_links', function (Blueprint $table): void {
            $table->unsignedBigInteger('follower_count')->nullable()->after('handle');
        });
    }

    public function down(): void
    {
        Schema::table('creator_social_links', function (Blueprint $table): void {
            $table->dropColumn('follower_count');
        });
    }
};
