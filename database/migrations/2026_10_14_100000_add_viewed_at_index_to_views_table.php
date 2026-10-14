<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The dashboard filters site-wide views by date without a viewable_type,
        // so the existing (viewable_type, viewable_id, viewed_at) index can't help.
        Schema::table('views', function (Blueprint $table) {
            $table->index('viewed_at', 'views_viewed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('views', function (Blueprint $table) {
            $table->dropIndex('views_viewed_at_index');
        });
    }
};
