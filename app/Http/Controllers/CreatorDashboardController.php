<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class CreatorDashboardController extends Controller
{
    /**
     * Show the logged-in creator's dashboard — a list of every creator
     * profile they've claimed, with quick edit access for each.
     */
    public function index(): View
    {
        $user = auth()->user();

        $creators = $user->claimedCreators()
            ->with('socialLinks')
            ->orderBy('name')
            ->get();

        return view('creators.dashboard', [
            'user' => $user,
            'creators' => $creators,
        ]);
    }
}
