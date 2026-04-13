<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            $table->string('dismissal_reason')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('researched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            $table->dropColumn(['dismissal_reason', 'responded_at']);
        });
    }
};
