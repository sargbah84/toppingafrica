<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users with search, role filter, and pagination.
     */
    public function index(Request $request): View
    {
        $query = User::with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role): void {
                $q->where('name', $role);
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'       => ['required', Password::defaults()],
            'role'           => ['nullable', 'string', 'exists:roles,name'],
            'is_staff'       => ['boolean'],
            'is_super_admin' => ['boolean'],
            'bio'            => ['nullable', 'string', 'max:1000'],
            'website'        => ['nullable', 'url', 'max:255'],
        ]);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'password'       => $validated['password'],
            'is_staff'       => $request->boolean('is_staff'),
            'is_super_admin' => $request->boolean('is_super_admin'),
            'bio'            => $validated['bio'] ?? null,
            'website'        => $validated['website'] ?? null,
        ]);

        // Single-role rule — syncRoles replaces any existing role with
        // exactly the one provided. On a fresh user this behaves the
        // same as assignRole, but kept consistent with the project rule.
        if (! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'       => ['nullable', Password::defaults()],
            'role'           => ['nullable', 'string', 'exists:roles,name'],
            'is_staff'       => ['boolean'],
            'is_super_admin' => ['boolean'],
            'bio'            => ['nullable', 'string', 'max:1000'],
            'website'        => ['nullable', 'url', 'max:255'],
        ]);

        $data = [
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'is_staff'       => $request->boolean('is_staff'),
            'is_super_admin' => $request->boolean('is_super_admin'),
            'bio'            => $validated['bio'] ?? null,
            'website'        => $validated['website'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        $user->syncRoles($validated['role'] ? [$validated['role']] : []);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle email verification status for a user.
     */
    public function toggleVerification(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => null])->save();

            return back()->with('success', "{$user->name} has been marked as unverified.");
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return back()->with('success', "{$user->name} has been marked as verified.");
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Prevent deleting self
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last super admin
        if ($user->is_super_admin) {
            $superAdminCount = User::where('is_super_admin', true)->count();
            if ($superAdminCount <= 1) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Cannot delete the last super admin.');
            }
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
