<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Roles & Permissions
        $permissions = [
            'manage posts', 'create posts', 'edit posts', 'delete posts', 'publish posts',
            'manage categories', 'manage tags', 'manage pages',
            'manage comments', 'moderate comments',
            'manage media',
            'manage menus',
            'manage ads',
            'manage newsletters',
            'manage users',
            'manage settings',
            'view analytics',
            'use ai tools',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $superAdmin = Role::create(['name' => 'super-admin']);
        $staff = Role::create(['name' => 'staff']);
        $editor = Role::create(['name' => 'editor']);
        $author = Role::create(['name' => 'author']);
        $regular = Role::create(['name' => 'regular']);
        // "creator" has no permissions — it's a marker role for routing.
        $creator = Role::create(['name' => 'creator']);

        $superAdmin->givePermissionTo(Permission::all());
        $editor->givePermissionTo([
            'manage posts', 'create posts', 'edit posts', 'publish posts',
            'manage categories', 'manage tags',
            'manage comments', 'moderate comments',
            'manage media', 'view analytics', 'use ai tools',
        ]);
        $author->givePermissionTo([
            'create posts', 'edit posts',
            'manage media', 'use ai tools',
        ]);

        // Admin User
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@toppingafrica.com',
            'password' => bcrypt('password'),
            'is_super_admin' => true,
            'is_staff' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super-admin');

        // Default Categories (matching toppingafrica.com)
        $categories = [
            ['name' => 'Africa', 'slug' => 'africa', 'color' => '#e74c3c', 'icon' => 'globe', 'order' => 1],
            ['name' => 'Business', 'slug' => 'business', 'color' => '#3498db', 'icon' => 'briefcase', 'order' => 2],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'color' => '#9b59b6', 'icon' => 'music', 'order' => 3],
            ['name' => 'Health', 'slug' => 'health', 'color' => '#2ecc71', 'icon' => 'heart', 'order' => 4],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'color' => '#f39c12', 'icon' => 'sun', 'order' => 5],
            ['name' => 'Politics', 'slug' => 'politics', 'color' => '#e67e22', 'icon' => 'landmark', 'order' => 6],
            ['name' => 'Sports', 'slug' => 'sports', 'color' => '#1abc9c', 'icon' => 'trophy', 'order' => 7],
            ['name' => 'Technology', 'slug' => 'technology', 'color' => '#34495e', 'icon' => 'cpu', 'order' => 8],
            ['name' => 'World', 'slug' => 'world', 'color' => '#2c3e50', 'icon' => 'globe-2', 'order' => 9],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Default Menus
        Menu::create(['name' => 'Main Navigation', 'location' => 'main']);
        Menu::create(['name' => 'Footer', 'location' => 'footer']);
    }
}
