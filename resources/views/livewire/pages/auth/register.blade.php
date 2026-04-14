<?php

use App\Models\User;
use App\Services\RecaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $recaptchaToken = '';

    // Honeypot — hidden field that bots fill but humans leave empty.
    // Named generically to avoid triggering form-fill extensions that target
    // common field names like "website", "url", "phone", etc.
    public string $hp_field = '';

    // Time gate — form must be open for at least 3 seconds
    public int $formLoadedAt = 0;

    public function mount(): void
    {
        $this->formLoadedAt = time();
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        // 1. Honeypot check — reject if the hidden field was filled
        if ($this->hp_field !== '') {
            $this->addError('email', 'Registration failed. Please try again.');
            return;
        }

        // 2. Time gate — reject if submitted too fast (< 3 seconds)
        if ($this->formLoadedAt > 0 && (time() - $this->formLoadedAt) < 3) {
            $this->addError('email', 'Registration failed. Please try again.');
            return;
        }

        // 3. Rate limit — max 3 registrations per IP per hour
        $throttleKey = 'register:' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = (int) ceil($seconds / 60);
            $this->addError('email', "Too many registration attempts. Please try again in {$minutes} minute(s).");
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                // 4. Name validation — reject bot-like random strings
                $name = trim($value);

                // Must contain at least one vowel
                if (!preg_match('/[aeiouAEIOU]/', $name)) {
                    $fail('Please enter a valid name.');
                    return;
                }

                // Reject if uppercase ratio is too high (> 60% of letters)
                $letters = preg_replace('/[^a-zA-Z]/', '', $name);
                if (strlen($letters) > 3) {
                    $upperCount = strlen(preg_replace('/[^A-Z]/', '', $letters));
                    if ($upperCount / strlen($letters) > 0.6) {
                        $fail('Please enter a valid name.');
                        return;
                    }
                }

                // Reject if name has a single "word" longer than 20 chars (random string)
                foreach (explode(' ', $name) as $word) {
                    if (strlen($word) > 20) {
                        $fail('Please enter a valid name.');
                        return;
                    }
                }
            }],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $recaptcha = app(RecaptchaService::class);
        if ($recaptcha->isEnabled()) {
            $result = $recaptcha->verify($this->recaptchaToken, 'register');
            if (!$result['success']) {
                $this->addError('recaptcha', $result['error'] ?? 'reCAPTCHA verification failed.');
                return;
            }
        }

        // All checks passed — count the attempt
        RateLimiter::hit($throttleKey, 3600);

        $validated['email'] = strtolower($validated['email']);
        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        // Single-role rule — every new registration is a 'regular' user.
        $user->syncRoles(['regular']);

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="flex items-start gap-4 mb-8">
        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 mt-1">
            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/></svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Create your account</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Join {{ \App\Models\Setting::get('site_name', config('app.name')) }} to stay connected with the latest content.</p>
        </div>
    </div>

    @php $recaptchaSiteKey = app(\App\Services\RecaptchaService::class)->getSiteKey(); @endphp

    <form x-data="{
              siteKey: '{{ $recaptchaSiteKey }}',
              submitting: false,
              async handleSubmit() {
                  if (this.submitting) return;
                  this.submitting = true;
                  try {
                      if (!this.siteKey) {
                          await $wire.register();
                          return;
                      }
                      // Wait up to 5s for the reCAPTCHA script to load
                      const deadline = Date.now() + 5000;
                      while (Date.now() < deadline && (typeof grecaptcha === 'undefined' || !grecaptcha.enterprise)) {
                          await new Promise(r => setTimeout(r, 100));
                      }
                      let token = 'RECAPTCHA_NOT_LOADED';
                      if (typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                          try {
                              token = await new Promise((resolve, reject) => {
                                  const t = setTimeout(() => reject(new Error('timeout')), 8000);
                                  grecaptcha.enterprise.ready(async () => {
                                      try {
                                          const tok = await grecaptcha.enterprise.execute(this.siteKey, { action: 'register' });
                                          clearTimeout(t);
                                          resolve(tok);
                                      } catch (e) { clearTimeout(t); reject(e); }
                                  });
                              });
                          } catch (e) {
                              token = 'RECAPTCHA_FAILED';
                          }
                      }
                      await $wire.set('recaptchaToken', token, false);
                      await $wire.register();
                  } finally {
                      this.submitting = false;
                  }
              }
          }"
          x-on:submit.prevent="handleSubmit()">
        @error('recaptcha')
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            </div>
        @enderror

        <!-- Honeypot — invisible to humans, bots auto-fill it.
             readonly + off-screen defeats most fake-data / form-filler extensions,
             which skip readonly inputs. Real bots submitting the form directly
             will still trip it by posting a non-empty value. -->
        <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; overflow: hidden;">
            <input wire:model="hp_field" id="hp_field" type="text" name="hp_field" tabindex="-1" autocomplete="off" readonly data-lpignore="true" data-1p-ignore="true" data-bwignore="true">
        </div>

        <!-- Time gate -->
        <input wire:model="formLoadedAt" type="hidden">

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                </span>
                <input wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name"
                       class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                       placeholder="Enter your full name">
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                </span>
                <input wire:model="email" id="email" type="email" name="email" required autocomplete="username"
                       class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                       placeholder="Enter your email">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ showPassword: false }">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                </span>
                <input wire:model="password" id="password"
                       :type="showPassword ? 'text' : 'password'"
                       name="password" required autocomplete="new-password"
                       class="w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                       placeholder="Create a password">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                </span>
                <input wire:model="password_confirmation" id="password_confirmation" type="password"
                       name="password_confirmation" required autocomplete="new-password"
                       class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                       placeholder="Confirm your password">
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <!-- Submit -->
        <button type="submit" class="w-full mt-6 py-3 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
            Register
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </button>
    </form>

    <!-- Login Link -->
    <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-6">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="text-primary hover:text-primary-hover font-semibold transition-colors">Login now</a>
    </p>
</div>
