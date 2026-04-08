<x-layouts.blog
    title="Claim Your Profile"
    :metaDescription="'Claim and customize your Topping Africa creator profile.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 md:p-8">
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-1">Claim Your Profile</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Hi {{ $creator->name }}! Update your profile below. Changes go live immediately and our team may review them.
            </p>

            @if(session('success'))
                <div class="mb-5 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('offer_register'))
                <div class="mb-6 rounded-lg bg-primary/10 dark:bg-primary/20 border border-primary/30 p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Want to edit this profile anytime?</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                Complete your account so you can come back later — and manage multiple creator profiles from one place.
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('creators.claim.register.show', ['token' => $creator->claim_token]) }}"
                                   class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary-hover transition-colors">
                                    Complete your account →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @php($recaptchaSiteKey = app(\App\Services\RecaptchaService::class)->getSiteKey())
            @php($formAction = ($authenticatedEdit ?? false)
                ? route('creators.claim.update-as-owner', ['creatorId' => $creator->id])
                : url('/creators/claim/' . $creator->claim_token))
            <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="space-y-5"
                  x-data="{ siteKey: @js($recaptchaSiteKey), submitting: false }"
                  x-on:submit.prevent="
                      if (submitting) return;
                      submitting = true;
                      const doSubmit = () => { $el.submit(); };
                      if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                          grecaptcha.enterprise.ready(async () => {
                              try {
                                  const token = await grecaptcha.enterprise.execute(siteKey, { action: 'submit_creator_claim' });
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

                @error('recaptcha')
                    <div class="rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                    </div>
                @enderror

                {{-- Current Photo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profile Photo</label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0">
                            @if($creator->profile_image_url)
                                <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold"
                                     style="background-color: {{ $creator->avatar_color }}">
                                    {{ $creator->initials }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" name="photo" accept="image/*"
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-primary file:text-white hover:file:bg-primary-hover
                                          file:cursor-pointer">
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG or WebP. Max 5MB.</p>
                        </div>
                    </div>
                    @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Bio --}}
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio</label>
                    <textarea name="bio" id="bio" rows="4"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">{{ old('bio', $creator->bio) }}</textarea>
                    @error('bio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Social Links --}}
                <div x-data="{ links: {{ json_encode($creator->socialLinks->map(fn($l) => ['platform' => $l->platform, 'url' => $l->url, 'handle' => $l->handle ?? ''])->values()) }} }">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Social Links</label>
                        <button type="button" @click="links.push({platform: 'instagram', url: '', handle: ''})"
                                class="text-xs text-primary hover:text-primary-hover font-medium">
                            + Add link
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(link, index) in links" :key="index">
                            <div class="flex items-center gap-2">
                                <select x-model="link.platform" :name="'social_links[' + index + '][platform]'"
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-xs w-28">
                                    <option value="youtube">YouTube</option>
                                    <option value="instagram">Instagram</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="twitter">Twitter/X</option>
                                    <option value="facebook">Facebook</option>
                                    <option value="website">Website</option>
                                </select>
                                <input x-model="link.url" :name="'social_links[' + index + '][url]'" type="url" placeholder="URL"
                                       class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                <input x-model="link.handle" :name="'social_links[' + index + '][handle]'" type="text" placeholder="Handle"
                                       class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                <button type="button" @click="links.splice(index, 1)" class="text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" :disabled="submitting"
                            class="w-full sm:w-auto px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary-hover transition-colors disabled:opacity-60">
                        <span x-show="!submitting">Save Changes</span>
                        <span x-show="submitting" x-cloak>Saving…</span>
                    </button>
                    <p class="mt-2 text-xs text-gray-400">Changes go live immediately. Our team may review them.</p>
                </div>
            </form>
        </div>
    </div>
</section>

</x-layouts.blog>
