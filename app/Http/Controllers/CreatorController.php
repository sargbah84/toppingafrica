<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\VerifiesRecaptcha;
use App\Mail\CreatorClaimInvite;
use App\Models\Creator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CreatorController extends Controller
{
    use VerifiesRecaptcha;

    // index() was removed: the public /creators URL is now a CMS page backed
    // by the 'creators' template and rendered by BlogController::renderCreatorsPage.

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

        $this->verifyRecaptcha($request, 'request_creator_claim');

        $creator = Creator::where('slug', $slug)
            ->where('status', '!=', 'claimed')
            ->firstOrFail();

        $email = $request->input('email');

        // Belt-and-braces guard — Laravel's `email` rule already ran above,
        // but we re-check with the same filter Symfony Mime uses to guarantee
        // no invalid address ever reaches the queued mail job.
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['email' => 'Please enter a valid email address.']);
        }

        $creator->update([
            'claim_token' => Str::uuid()->toString(),
            'claim_token_expires_at' => now()->addHours(48),
            'claimed_by_email' => $email,
        ]);

        Mail::to($email)->queue(new CreatorClaimInvite($creator->fresh()));

        return back()->with('success', 'A claim link has been sent to your email. Check your inbox!');
    }
}
