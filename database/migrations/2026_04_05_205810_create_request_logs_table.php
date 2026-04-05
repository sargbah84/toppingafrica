<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->text('url');
            $table->unsignedSmallInteger('status_code');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->string('route_name')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('status_code');
            $table->index('created_at');
            $table->index(['status_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
