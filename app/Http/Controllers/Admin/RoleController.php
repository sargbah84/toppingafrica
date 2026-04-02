<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with permission and user counts.
     */
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        $permissions = Permission::orderBy('name')->get();
        $groupedPermissions = $this->groupPermissions($permissions);

        return view('admin.roles.create', compact('groupedPermissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (! empty($validated['permissions'])) {
            $role->givePermissionTo($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        $permissions = Permission::orderBy('name')->get();
        $groupedPermissions = $this->groupPermissions($permissions);
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'groupedPermissions', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Delete the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete a role that has users assigned to it.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Group permissions by their prefix (e.g., "manage", "create", "edit").
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function groupPermissions(\Illuminate\Database\Eloquent\Collection $permissions): array
    {
        $groups = [
            'Posts'       => ['manage posts', 'create posts', 'edit posts', 'delete posts', 'publish posts'],
            'Content'     => ['manage categories', 'manage tags', 'manage pages', 'manage menus'],
            'Comments'    => ['manage comments', 'moderate comments'],
            'Media'       => ['manage media'],
            'Marketing'   => ['manage ads', 'manage newsletters'],
            'Users'       => ['manage users'],
            'System'      => ['manage settings', 'view analytics', 'use ai tools'],
        ];

        $grouped = [];
        $assigned = [];

        foreach ($groups as $group => $permNames) {
            $grouped[$group] = $permissions->filter(function ($perm) use ($permNames, &$assigned) {
                if (in_array($perm->name, $permNames)) {
                    $assigned[] = $perm->name;

                    return true;
                }

                return false;
            });
        }

        // Catch any permissions not in predefined groups
        $remaining = $permissions->filter(fn ($perm) => ! in_array($perm->name, $assigned));
        if ($remaining->isNotEmpty()) {
            $grouped['Other'] = $remaining;
        }

        return $grouped;
    }
}
