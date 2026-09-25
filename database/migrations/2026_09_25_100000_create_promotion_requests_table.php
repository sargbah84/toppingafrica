<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Order
            $table->string('package', 40);
            $table->json('addons')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3);

            // Contact
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();

            // What is being promoted
            $table->string('promo_type', 40);
            $table->string('subject_name');
            $table->string('title');
            $table->text('description');
            $table->string('primary_url', 500);
            $table->json('links')->nullable();
            $table->date('preferred_date')->nullable();
            $table->boolean('rights_confirmed')->default(false);

            // Workflow
            $table->string('status', 30)->default('inquiry')->index();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->json('deliverables')->nullable();
            $table->json('metrics')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('live_at')->nullable();

            $table->timestamps();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_sponsored')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('is_sponsored');
        });

        Schema::dropIfExists('promotion_requests');
    }
};
