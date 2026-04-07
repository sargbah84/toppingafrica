<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\CreatorClaimInvite;
use App\Models\Creator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CreatorController extends Controller
{
    public function index(Request $request): View
    {
        $query = Creator::with('socialLinks')
            ->where(function ($q) {
                $q->where('status', 'published')
                  ->orWhere('status', 'claimed');
            });

        $hasFilter = false;

        if ($category = $request->query('category')) {
            $query->where('category', $category);
            $hasFilter = true;
        }

        if ($country = $request->query('country')) {
            $query->where('country', $country);
            $hasFilter = true;
        }

        // When unfiltered, mix countries and niches together so the page feels diverse.
        // Use a per-page seed so pagination stays consistent within a visit.
        if ($hasFilter) {
            $query->latest();
        } else {
            $seed = (int) ($request->query('seed') ?: now()->format('Ymd'));
            $query->inRandomOrder($seed);
        }

        $creators = $query->paginate(24)->withQueryString();

        $categories = Creator::where(function ($q) {
            $q->where('status', 'published')->orWhere('status', 'claimed');
        })->distinct()->pluck('category')->sort()->values();

        $countries = Creator::where(function ($q) {
            $q->where('status', 'published')->orWhere('status', 'claimed');
        })->distinct()->pluck('country')->sort()->values();

        return view('creators.index', compact('creators', 'categories', 'countries'));
    }

    public function show(string $slug): View
    {
        $creator = Creator::with('socialLinks')
            ->where('slug', $slug)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('status', 'claimed');
            })
            ->withCount('followers')
            ->firstOrFail();

        $isFollowing = $creator->isFollowedBy(auth()->user());

        return view('creators.show', compact('creator', 'isFollowing'));
    }

    public function toggleFollow(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['error' => 'Login required.'], 401);
        }

        $creator = Creator::where('slug', $slug)->firstOrFail();

        if ($creator->isFollowedBy($request->user())) {
            $creator->followers()->detach($request->user()->id);
            $following = false;
        } else {
            $creator->followers()->syncWithoutDetaching([$request->user()->id]);
            $following = true;
        }

        return response()->json([
            'following' => $following,
            'count' => $creator->followers()->count(),
        ]);
    }

    public function requestClaim(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $creator = Creator::where('slug', $slug)
            ->where('status', '!=', 'claimed')
            ->firstOrFail();

        $creator->update([
            'claim_token' => Str::uuid()->toString(),
            'claim_token_expires_at' => now()->addHours(48),
            'claimed_by_email' => $request->input('email'),
        ]);

        Mail::to($request->input('email'))->queue(new CreatorClaimInvite($creator->fresh()));

        return back()->with('success', 'A claim link has been sent to your email. Check your inbox!');
    }
}
