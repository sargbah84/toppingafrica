<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user. Only super admins can impersonate.
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if (! $admin->is_super_admin) {
            abort(403, 'Only super admins can impersonate users.');
        }

        // Cannot impersonate yourself
        if ($user->id === $admin->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        // Store the original admin ID in session
        session()->put('impersonating_from', $admin->id);

        Auth::login($user);

        // Redirect staff users to admin, regular users to the homepage.
        $destination = ($user->is_staff || $user->is_super_admin)
            ? route('admin.dashboard')
            : '/';

        return redirect($destination)
            ->with('success', "Now impersonating {$user->name}.");
    }

    /**
     * Stop impersonating and return to the original admin account.
     */
    public function leave(): RedirectResponse
    {
        $adminId = session()->pull('impersonating_from');

        if (! $adminId) {
            return redirect()->route('admin.dashboard');
        }

        $admin = User::findOrFail($adminId);

        Auth::login($admin);

        return redirect()->route('admin.users.index')
            ->with('success', 'Impersonation ended. Welcome back!');
    }
}
