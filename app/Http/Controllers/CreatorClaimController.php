<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\VerifiesRecaptcha;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CreatorClaimController extends Controller
{
    use VerifiesRecaptcha;

    public function show(string $token): RedirectResponse|View
    {
        $creator = Creator::with('socialLinks')
            ->where('claim_token', $token)
            ->where('claim_token_expires_at', '>', now())
            ->firstOrFail();

        // Magic-link login: if this creator is already linked to a User
        // account (i.e. someone has registered after a previous claim),
        // clicking the email link logs them in automatically and sends them
        // to their dashboard. The token effectively becomes a passwordless
        // login link for already-registered creators.
        if ($creator->user_id !== null) {
            Auth::loginUsingId($creator->user_id, remember: true);
            request()->session()->regenerate();

            // Token has done its job — clear it so it can't be reused or leaked.
            $creator->forceFill([
                'claim_token' => null,
                'claim_token_expires_at' => null,
            ])->save();

            return redirect()->route('creator.dashboard');
        }

        return view('creators.claim', compact('creator'));
    }

    /**
     * Authenticated edit route — used by registered creators editing their
     * own profile from the dashboard. No token needed; ownership is checked
     * via the user_id link.
     */
    public function editAsOwner(int $creatorId): View
    {
        $creator = Creator::with('socialLinks')->findOrFail($creatorId);

        abort_unless($creator->canBeEditedBy(auth()->user()), 403);

        return view('creators.claim', [
            'creator' => $creator,
            'authenticatedEdit' => true,
        ]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $creator = Creator::with('socialLinks')
            ->where('claim_token', $token)
            ->where('claim_token_expires_at', '>', now())
            ->firstOrFail();

        $this->applyClaimUpdate($request, $creator);

        // If the claimant is not yet logged in (typical first-time claim flow),
        // send them back to the claim edit page with a flash banner offering
        // account registration so they can come back and edit later. If they
        // are logged in (e.g. coming from the creator dashboard), send them
        // to the dashboard.
        if (auth()->check()) {
            return redirect()->route('creator.dashboard')
                ->with('success', 'Your changes are live. Our team may review them shortly.');
        }

        return redirect()->route('creators.claim', ['token' => $token])
            ->with('success', 'Your changes are live. Our team may review them shortly.')
            ->with('offer_register', true);
    }

    /**
     * Authenticated counterpart to update() — used by logged-in creators
     * editing their own profile via the dashboard. Authorization is by
     * user_id ownership rather than by token.
     */
    public function updateAsOwner(Request $request, int $creatorId): RedirectResponse
    {
        $creator = Creator::with('socialLinks')->findOrFail($creatorId);

        abort_unless($creator->canBeEditedBy(auth()->user()), 403);

        $this->applyClaimUpdate($request, $creator);

        return redirect()->route('creator.dashboard')
            ->with('success', 'Your changes are live. Our team may review them shortly.');
    }

    /**
     * Shared mutation logic for both the token-based and authenticated
     * edit paths. Validates input, runs reCAPTCHA, writes the bio/photo,
     * replaces social links, and flips the pending_claim_edit flag.
     */
    private function applyClaimUpdate(Request $request, Creator $creator): void
    {
        $request->validate([
            'bio' => 'required|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'required|string',
            'social_links.*.url' => 'nullable|url',
        ]);

        $this->verifyRecaptcha($request, 'submit_creator_claim');

        if ($request->hasFile('photo')) {
            $creator->addMediaFromRequest('photo')->toMediaCollection('profile_image');
        }

        $creator->update([
            'bio' => $request->input('bio'),
            'pending_claim_edit' => true,
        ]);

        if ($request->has('social_links')) {
            $creator->socialLinks()->delete();

            foreach ($request->input('social_links', []) as $linkData) {
                if (empty($linkData['url'])) {
                    continue;
                }

                $creator->socialLinks()->create([
                    'platform' => $linkData['platform'],
                    'url' => $linkData['url'],
                    'handle' => $linkData['handle'] ?? null,
                ]);
            }
        }
    }

    /**
     * Show the "Complete your account" registration form for a claimant.
     * Pre-populates the email from the creator's claimed_by_email and
     * detects email collisions with existing users — in which case the
     * view falls through to a "log in to link this profile" message
     * instead of the password-creation form.
     */
    public function showRegister(string $token): View|RedirectResponse
    {
        $creator = Creator::where('claim_token', $token)
            ->where('claim_token_expires_at', '>', now())
            ->firstOrFail();

        // If they're already logged in, no point asking them to register.
        // Just link this creator to their existing user (if not already
        // linked) and send them to the dashboard.
        if (auth()->check()) {
            if ($creator->user_id === null) {
                $this->linkCreatorToUser($creator, auth()->user());
            }

            return redirect()->route('creator.dashboard');
        }

        $email = $creator->claimed_by_email;

        // Email collision: an existing user already owns this address.
        // The view will render a login form instead of a register form.
        $existingUser = $email ? User::where('email', $email)->first() : null;

        return view('creators.register', [
            'creator' => $creator,
            'email' => $email,
            'token' => $token,
            'existingUser' => $existingUser,
        ]);
    }

    /**
     * Process the "Complete your account" form submission. Creates a new
     * User with the creator role, links the creator row to it, clears
     * the claim token, logs the user in, and sends them to the dashboard.
     */
    public function register(Request $request, string $token): RedirectResponse
    {
        $creator = Creator::where('claim_token', $token)
            ->where('claim_token_expires_at', '>', now())
            ->firstOrFail();

        if (! $creator->claimed_by_email) {
            return back()->withErrors([
                'email' => 'No claimed email found for this profile. Please request a new claim link.',
            ]);
        }

        // Reject if the email is already in use — the view should have
        // routed them to the login form instead, so reaching this branch
        // means a tampered submission.
        if (User::where('email', $creator->claimed_by_email)->exists()) {
            return redirect()
                ->route('creators.claim.register.show', ['token' => $token])
                ->withErrors(['email' => 'This email is already registered. Please log in below.']);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->verifyRecaptcha($request, 'creator_register');

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $creator->claimed_by_email,
            'password' => Hash::make($request->input('password')),
            'email_verified_at' => now(), // verified by virtue of clicking the email link
        ]);

        $user->assignRole('creator');

        $this->linkCreatorToUser($creator, $user);

        event(new Registered($user));

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('creator.dashboard')
            ->with('success', "Welcome, {$user->name}! Your account is set up.");
    }

    /**
     * Link a creator row to a user, clear the claim token, and ensure
     * the user has the "creator" role. Idempotent.
     */
    private function linkCreatorToUser(Creator $creator, User $user): void
    {
        $creator->forceFill([
            'user_id' => $user->id,
            'claim_token' => null,
            'claim_token_expires_at' => null,
            'claimed_at' => $creator->claimed_at ?? now(),
            'status' => $creator->status === 'pending' ? 'claimed' : $creator->status,
        ])->save();

        if (! $user->hasRole('creator')) {
            $user->assignRole('creator');
        }
    }
}
