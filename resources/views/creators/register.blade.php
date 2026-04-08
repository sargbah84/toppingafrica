<x-layouts.blog
    title="Complete Your Account"
    :metaDescription="'Complete your Topping Africa creator account.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 md:p-8">

            @if($existingUser)
                {{-- Email collision: existing user — show a "log in to link" message --}}
                <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-1">Email Already Registered</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    The email <strong>{{ $email }}</strong> already has a Topping Africa account.
                    Log in below and we'll automatically link <strong>{{ $creator->name }}</strong> to your account.
                </p>

                <a href="{{ route('login') }}?return={{ urlencode(route('creators.claim.register.show', ['token' => $token])) }}"
                   class="block w-full text-center px-4 py-2.5 bg-primary text-white text-sm font-bold rounded-md hover:bg-primary-hover transition-colors">
                    Log in to your account
                </a>

                <p class="mt-4 text-xs text-gray-400 text-center">
                    Forgot your password? <a href="{{ route('password.request') }}" class="text-primary hover:underline">Reset it</a>
                </p>
            @else
                {{-- New user: show the registration form --}}
                <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-1">Complete Your Account</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Set a password so you can manage <strong>{{ $creator->name }}</strong> anytime.
                </p>

                @if($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                        <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php($recaptchaSiteKey = app(\App\Services\RecaptchaService::class)->getSiteKey())
                <form action="{{ route('creators.claim.register', ['token' => $token]) }}" method="POST" class="space-y-4"
                      x-data="{ siteKey: @js($recaptchaSiteKey), submitting: false }"
                      x-on:submit.prevent="
                          if (submitting) return;
                          submitting = true;
                          const doSubmit = () => { $el.submit(); };
                          if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                              grecaptcha.enterprise.ready(async () => {
                                  try {
                                      const token = await grecaptcha.enterprise.execute(siteKey, { action: 'creator_register' });
                                      $refs.recaptchaToken.value = token;
                                  } catch (e) {
                                      $refs.recaptchaToken.value = 'RECAPTCHA_FAILED';
                                  }
                                  doSubmit();
                              });
                          } else if (siteKey) {
                              $refs.recaptchaToken.value = 'RECAPTCHA_NOT_LOADED';
                              doSubmit();
                          } else {
                              doSubmit();
                          }
                      ">
                    @csrf
                    <input type="hidden" name="recaptcha_token" x-ref="recaptchaToken" value="">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" value="{{ $email }}" disabled
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed">
                        <p class="mt-1 text-xs text-gray-400">This is the email you used to claim the profile.</p>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $creator->name) }}" required
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                        <input type="password" name="password" id="password" required autocomplete="new-password"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    </div>

                    <button type="submit" :disabled="submitting"
                            class="w-full px-4 py-2.5 bg-primary text-white text-sm font-bold rounded-md hover:bg-primary-hover transition-colors disabled:opacity-60">
                        <span x-show="!submitting">Create my account</span>
                        <span x-show="submitting" x-cloak>Creating account…</span>
                    </button>
                </form>

                <p class="mt-4 text-xs text-gray-400 text-center">
                    Already have an account? <a href="{{ route('login') }}" class="text-primary hover:underline">Log in</a>
                </p>
            @endif

        </div>
    </div>
</section>

</x-layouts.blog>
