<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleAuthController extends Controller
{
    private function driver(): GoogleProvider
    {
        // Use the dedicated google_oauth client (separate from the Search
        // Console client stored under services.google).
        return Socialite::buildProvider(GoogleProvider::class, config('services.google_oauth'));
    }

    public function redirect(): RedirectResponse
    {
        return $this->driver()->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = $this->driver()->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        $email = strtolower((string) $googleUser->getEmail());
        if ($email === '') {
            return redirect()->route('login')->with('error', 'Google sign-in did not return an email address.');
        }

        // 1. Match by provider_id first (existing Google-linked user)
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->getId())
            ->first();

        // 2. Otherwise match by email — silently link the Google identity to the existing account
        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        $isNew = false;

        if ($user) {
            $updates = [];
            if ($user->provider !== 'google') {
                $updates['provider'] = 'google';
                $updates['provider_id'] = $googleUser->getId();
            }
            if (!$user->hasVerifiedEmail()) {
                $updates['email_verified_at'] = now();
            }
            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $updates['avatar'] = $googleUser->getAvatar();
            }
            if (!empty($updates)) {
                $user->forceFill($updates)->save();
            }
        } else {
            $isNew = true;
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'User',
                'email' => $email,
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            event(new Registered($user));
            $user->syncRoles(['regular']);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        $default = ($user->is_staff || $user->is_super_admin)
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }
}
