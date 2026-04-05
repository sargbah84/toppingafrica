<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard');

        // Create new roles if they don't exist
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'regular', 'guard_name' => $guard]);

        // Assign 'regular' to all users who have no role
        $usersWithoutRoles = \App\Models\User::doesntHave('roles')->get();
        foreach ($usersWithoutRoles as $user) {
            if ($user->is_super_admin) {
                $user->assignRole('super-admin');
            } elseif ($user->is_staff) {
                $user->assignRole('staff');
            } else {
                $user->assignRole('regular');
            }
        }

        // Also ensure existing staff/super-admin users have the right roles
        \App\Models\User::where('is_super_admin', true)->get()->each(function ($user) {
            if (!$user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        });
        \App\Models\User::where('is_staff', true)->where('is_super_admin', false)->get()->each(function ($user) {
            if (!$user->hasRole('staff')) {
                $user->assignRole('staff');
            }
        });
    }

    public function down(): void
    {
        // Roles are kept on rollback
    }
};
