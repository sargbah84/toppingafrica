<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Use firstOrCreate so re-running on environments where the role
        // already exists (e.g. fresh seeded local) is a no-op.
        // The "creator" role has no permissions — it's purely a marker
        // used for routing (login redirects this role to the creator
        // dashboard rather than the admin or generic dashboard).
        Role::query()->firstOrCreate(['name' => 'creator', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Role::query()->where('name', 'creator')->where('guard_name', 'web')->delete();
    }
};
