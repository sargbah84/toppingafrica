<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('pinned_section', 32)->nullable()->after('is_featured');
            $table->timestamp('pinned_until')->nullable()->after('pinned_section');

            $table->index(['pinned_section', 'pinned_until'], 'posts_pinned_section_until_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_pinned_section_until_index');
            $table->dropColumn(['pinned_section', 'pinned_until']);
        });
    }
};
