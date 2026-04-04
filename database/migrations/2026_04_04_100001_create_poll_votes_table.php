<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('option_index');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['post_id', 'user_id']);
            $table->index(['post_id', 'option_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
