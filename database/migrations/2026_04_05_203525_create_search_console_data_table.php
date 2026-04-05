<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_console_data', function (Blueprint $table) {
            $table->id();
            $table->string('query')->nullable();
            $table->string('page')->nullable();
            $table->string('country', 10)->nullable();
            $table->string('device', 20)->nullable();
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            $table->decimal('position', 8, 2)->default(0);
            $table->timestamps();

            $table->index('date');
            $table->index('query');
            $table->index('page');
            $table->index(['date', 'query']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_data');
    }
};
