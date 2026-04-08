<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            // Nullable: claiming a creator profile no longer requires registering
            // an account. The link is created when (and if) the claimant decides
            // to "Complete your account" from the post-claim banner. A creator
            // can also be linked to a user later via the request-claim flow when
            // an already-logged-in user claims another profile.
            //
            // claimed_by_email is intentionally kept (not dropped) — it remains
            // the historical record of who first claimed the profile, regardless
            // of whether they later registered a user account.
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
