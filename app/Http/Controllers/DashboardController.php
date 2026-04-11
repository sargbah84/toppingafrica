<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Unified frontend dashboard for non-admin users.
 *
 * Every non-staff role (creator, regular, author, editor, future roles)
 * lands here after login. The landing page branches by role to show the
 * most useful content for that user:
 *
 *   - creator  → their claimed profile cards
 *   - anything else → their Recently Viewed + Comments history, inline
 *
 * Admin/staff users continue to go to /admin via the login redirect.
 * This controller is not for them.
 *
 * Under the single-role rule (see memory: single-role rule) every user
 * has exactly one role, so there's no multi-role ambiguity when deciding
 * which branch to render.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $creators = collect();
        if ($user->hasRole('creator')) {
            $creators = $user->claimedCreators()
                ->with('socialLinks')
                ->withCount('views')
                ->orderBy('name')
                ->get();
        }

        return view('dashboard.index', [
            'user' => $user,
            'creators' => $creators,
        ]);
    }

    /**
     * Account settings — name/email/avatar/bio, password change, and
     * delete account. Embeds the existing Breeze Livewire forms inside
     * the dashboard layout with sidebar.
     *
     * Replaces the old /my-account/profile URL (which now 301-redirects
     * here).
     */
    public function settings(): View
    {
        return view('dashboard.settings', [
            'user' => auth()->user(),
        ]);
    }
}
