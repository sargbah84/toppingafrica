<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('feature', 50);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('is_successful')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('provider');
            $table->index('feature');
            $table->index('created_at');
            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
