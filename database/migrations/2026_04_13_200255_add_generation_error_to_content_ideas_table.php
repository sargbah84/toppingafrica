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
            $table->text('generation_error')->nullable()->after('generated_post_id');
            $table->unsignedBigInteger('generation_started_by')->nullable()->after('generation_error');
        });
    }

    public function down(): void
    {
        Schema::table('content_ideas', function (Blueprint $table) {
            $table->dropColumn(['generation_error', 'generation_started_by']);
        });
    }
};
