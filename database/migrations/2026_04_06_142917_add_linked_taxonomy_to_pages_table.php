<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('linked_category_id')->nullable()->after('template')->constrained('categories')->nullOnDelete();
            $table->foreignId('linked_tag_id')->nullable()->after('linked_category_id')->constrained('tags')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_category_id');
            $table->dropConstrainedForeignId('linked_tag_id');
        });
    }
};
