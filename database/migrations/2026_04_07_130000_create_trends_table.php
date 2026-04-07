<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trends', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary');
            $table->string('category');
            $table->string('country');
            $table->string('country_code', 2)->nullable();
            $table->string('source_label')->nullable();
            $table->string('source_url')->nullable();
            $table->date('trend_date');
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
            $table->index('trend_date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trends');
    }
};
