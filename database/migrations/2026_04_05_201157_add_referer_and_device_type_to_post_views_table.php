<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->string('referer')->nullable()->after('user_agent');
            $table->string('device_type', 20)->nullable()->after('referer');

            $table->index('device_type');
            $table->index('referer');
        });
    }

    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex(['device_type']);
            $table->dropIndex(['referer']);
            $table->dropColumn(['referer', 'device_type']);
        });
    }
};
