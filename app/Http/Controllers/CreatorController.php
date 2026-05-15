<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\VerifiesRecaptcha;
use App\Mail\CreatorClaimInvite;
use App\Models\Creator;
use App\Services\Creator\CreatorQrCodeService;
use Illuminate\Http\JsonResponse;
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

    public function suggest(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim($request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $creators = Creator::query()
            ->where(fn ($query) => $query->where('status', 'published')->orWhere('status', 'claimed'))
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('category', 'like', "%{$q}%")
                      ->orWhere('country', 'like', "%{$q}%");
            })
            ->limit(6)
            ->get();

        return response()->json($creators->map(fn ($c) => [
            'name' => $c->name,
            'slug' => $c->slug,
            'category' => $c->category,
            'country' => $c->country,
            'image' => $c->profile_image_url,
            'initials' => strtoupper(substr($c->name, 0, 1)),
            'url' => route('creators.show', $c->slug),
        ]));
    }

    public function show(string $slug, Request $request): View
    {
        $creator = Creator::with('socialLinks')
            ->where('slug', $slug)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('status', 'claimed');
            })
            ->withCount('followers')
            ->firstOrFail();

        $isFollowing = $creator->isFollowedBy(auth()->user());

        // True when the currently-authenticated viewer owns this creator —
        // used by the view to swap the "Claim this profile" CTA for an
        // "Edit your profile" shortcut. False for guests and for other
        // logged-in users who don't own this row.
        $isOwner = auth()->check() && $creator->user_id === auth()->id();

        // True when the profile has been claimed by anyone (owner or not).
        // Used to render a disabled "Claimed" pill for non-owner viewers.
        $isClaimed = $creator->user_id !== null || $creator->status === 'claimed';

        // Track the view. Skip owners viewing their own profile so creators
        // don't inflate their own counts while testing edits. Staff IP
        // exclusions are still applied via the global exclusion_rules setting
        // inside View::recordView().
        if (! $isOwner) {
            app(\App\Services\ViewTracker::class)->track($creator, $request);
        }

        // True when the current user can edit this profile inline (owner, staff, or super-admin).
        $canEdit = $creator->canBeEditedBy(auth()->user());

        // Published posts that feature this creator (via the post_creator pivot).
        $featuredPosts = $creator->posts()
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return view('creators.show', compact('creator', 'isFollowing', 'isOwner', 'isClaimed', 'canEdit', 'featuredPosts'));
    }

    public function qrCard(string $slug, CreatorQrCodeService $service): JsonResponse
    {
        $creator = Creator::with('socialLinks')
            ->where('slug', $slug)
            ->where(fn ($q) => $q->where('status', 'published')->orWhere('status', 'claimed'))
            ->firstOrFail();

        // Route the avatar through our own /creators/{slug}/avatar proxy so
        // the canvas can draw it without CORS errors (S3 doesn't emit
        // Access-Control-Allow-Origin for uploaded media).
        $avatarUrl = $creator->profile_image_url
            ? route('creators.avatar', $creator->slug)
            : null;

        return response()->json([
            'name' => $creator->name,
            'category' => $creator->category,
            'country' => $creator->country,
            'avatar_url' => $avatarUrl,
            'avatar_color' => $creator->avatar_color,
            'initials' => $creator->initials,
            'vcard' => $service->buildVCard($creator),
            'profile_url' => url($creator->public_url),
        ]);
    }

    public function trackQrDownload(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'format' => 'required|string|in:png,jpeg,share,clipboard',
        ]);

        $creator = Creator::where('slug', $slug)
            ->where(fn ($q) => $q->where('status', 'published')->orWhere('status', 'claimed'))
            ->firstOrFail();

        \Illuminate\Support\Facades\DB::table('creator_qr_downloads')->insert([
            'creator_id' => $creator->id,
            'user_id' => $request->user()?->id,
            'format' => $data['format'],
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent' => \Illuminate\Support\Str::limit((string) $request->userAgent(), 500, ''),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function avatar(string $slug): \Symfony\Component\HttpFoundation\Response
    {
        $creator = Creator::where('slug', $slug)
            ->where(fn ($q) => $q->where('status', 'published')->orWhere('status', 'claimed'))
            ->firstOrFail();

        $source = $creator->profile_image_url;

        if (! $source) {
            abort(404);
        }

        $cacheKey = "creator-avatar:{$creator->id}:" . ($creator->updated_at?->timestamp ?? 0);

        // Use the file cache store — the default DB cache rejects binary
        // image bytes with "Incorrect string value" on MySQL utf8mb4 columns.
        [$body, $contentType] = \Illuminate\Support\Facades\Cache::store('file')->remember($cacheKey, now()->addDay(), function () use ($source) {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($source);
            if (! $response->successful()) {
                return [null, null];
            }

            return [$response->body(), $response->header('Content-Type') ?: 'image/jpeg'];
        });

        if (! $body) {
            abort(502);
        }

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
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
            'claim_type' => 'required|in:self,referral',
        ]);

        $this->verifyRecaptcha($request, 'request_creator_claim');

        $creator = Creator::where('slug', $slug)->firstOrFail();

        $isReferral = $request->input('claim_type') === 'referral';

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
        // through to the standard email-verification flow. Only applies to
        // self-claims, not referrals.
        $user = auth()->user();
        if (! $isReferral
            && $user
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

            // Single-role rule — syncRoles replaces the user's existing
            // role with exactly 'creator', whether they were a regular
            // user or already a creator claiming a second profile.
            $user->syncRoles(['creator']);

            return redirect()->route('creators.claim.edit-as-owner', ['creatorId' => $creator->id])
                ->with('success', "You've claimed {$creator->name}. Update your profile below.");
        }

        $creator->update([
            'claim_token' => Str::uuid()->toString(),
            'claim_token_expires_at' => now()->addHours(48),
            'claimed_by_email' => $email,
        ]);

        Mail::to($email)->queue(new CreatorClaimInvite($creator->fresh(), $isReferral));

        $successMessage = $isReferral
            ? "An invite has been sent to {$email}. Thanks for helping {$creator->name} claim their profile!"
            : 'A claim link has been sent to your email. Check your inbox!';

        return back()->with('success', $successMessage);
    }
}
