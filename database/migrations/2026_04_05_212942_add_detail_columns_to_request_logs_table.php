<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_logs', function (Blueprint $table) {
            $table->text('exception')->nullable()->after('error_message');
            $table->text('response_body')->nullable()->after('exception');
        });
    }

    public function down(): void
    {
        Schema::table('request_logs', function (Blueprint $table) {
            $table->dropColumn(['exception', 'response_body']);
        });
    }
};
