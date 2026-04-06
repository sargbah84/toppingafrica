<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_histories', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->string('queue')->default('default');
            $table->string('status'); // completed, failed
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('exception')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'finished_at']);
            $table->index('finished_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_histories');
    }
};
