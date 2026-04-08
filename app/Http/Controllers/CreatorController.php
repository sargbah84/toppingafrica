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

        $creator = Creator::where('slug', $slug)->firstOrFail();

        // Already-claimed creators can still be re-claimed by their existing
        // owner via the "logged in shortcut" branch below; otherwise we
        // refuse to issue a fresh token for an actively-owned profile.
        $isAlreadyClaimedBySomeoneElse = $creator->status === 'claimed'
            && (! auth()->check() || $creator->user_id !== auth()->id());
        if ($isAlreadyClaimedBySomeoneElse) {
            abort(404);
        }

        $email = $request->input('email');

        // Belt-and-braces guard — Laravel's `email` rule already ran above,
        // but we re-check with the same filter Symfony Mime uses to guarantee
        // no invalid address ever reaches the queued mail job.
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['email' => 'Please enter a valid email address.']);
        }

        // Logged-in shortcut: if the authenticated user's own email matches
        // BOTH the form's submitted email AND the creator's contact email,
        // we have unambiguous proof of identity — link the creator to this
        // user immediately and skip the email round-trip. Any mismatch falls
        // through to the standard email-verification flow.
        $user = auth()->user();
        if ($user
            && strcasecmp($user->email, $email) === 0
            && $creator->contact_email
            && strcasecmp($creator->contact_email, $email) === 0
        ) {
            $creator->forceFill([
                'user_id' => $user->id,
                'claimed_by_email' => $email,
                'claimed_at' => $creator->claimed_at ?? now(),
                'status' => 'claimed',
                'pending_claim_edit' => true,
                'claim_token' => null,
                'claim_token_expires_at' => null,
            ])->save();

            if (! $user->hasRole('creator')) {
                $user->assignRole('creator');
            }

            return redirect()->route('creators.claim.edit-as-owner', ['creatorId' => $creator->id])
                ->with('success', "You've claimed {$creator->name}. Update your profile below.");
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
