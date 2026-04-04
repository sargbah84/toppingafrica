<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['modal', 'announcement_bar', 'slide_in', 'full_screen', 'bottom_bar'])->default('modal');
            $table->longText('content')->nullable();
            $table->boolean('is_active')->default(false);

            // Trigger settings
            $table->enum('trigger_type', ['click', 'exit_intent', 'scroll', 'timed', 'page_load'])->default('page_load');
            $table->string('trigger_value', 50)->nullable();
            $table->unsignedInteger('frequency_days')->default(0);

            // Display rules
            $table->enum('display_on', ['all_pages', 'specific_pages'])->default('all_pages');
            $table->json('show_on_pages')->nullable();
            $table->json('exclude_pages')->nullable();
            $table->enum('device', ['all', 'mobile', 'desktop'])->default('all');
            $table->unsignedInteger('priority')->default(10);

            // Style settings
            $table->enum('position', ['center', 'top', 'bottom', 'left', 'right', 'full'])->default('center');
            $table->unsignedInteger('width_px')->default(600);
            $table->unsignedInteger('max_width_vw')->default(90);
            $table->enum('animation', ['fade_in', 'slide_down', 'slide_up', 'slide_left', 'slide_right', 'zoom_in', 'none'])->default('fade_in');
            $table->boolean('show_overlay')->default(true);
            $table->boolean('close_on_overlay_click')->default(true);
            $table->boolean('show_close_button')->default(true);
            $table->enum('shadow', ['none', 'sm', 'md', 'lg', 'xl'])->default('lg');
            $table->unsignedInteger('border_radius')->default(12);
            $table->unsignedInteger('border_width')->default(0);
            $table->string('border_color', 20)->default('#e5e7eb');
            $table->unsignedInteger('padding')->default(0);
            $table->string('bg_color', 20)->default('#ffffff');
            $table->string('overlay_color', 30)->default('rgba(0,0,0,0.6)');

            // Scheduling
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'display_on']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
