<section class="max-w-container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">Submit Your Creator Profile</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Fill in your details below. Your profile will be reviewed by our team before going live.</p>
        </div>

        {{-- Form Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8">

            @if(session('error'))
                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                    <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                </div>
            @endif

            @error('recaptcha')
                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                </div>
            @enderror

            <form x-data="{ siteKey: @js($this->getRecaptchaSiteKey()) }"
                  @submit.prevent="
                      if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                          grecaptcha.enterprise.ready(async () => {
                              try {
                                  const token = await grecaptcha.enterprise.execute(siteKey, { action: 'submit_creator_profile' });
                                  $wire.set('recaptchaToken', token);
                              } catch (e) {
                                  $wire.set('recaptchaToken', 'RECAPTCHA_FAILED');
                              }
                              $wire.submit();
                          });
                      } else if (siteKey) {
                          $wire.set('recaptchaToken', 'RECAPTCHA_NOT_LOADED');
                          $wire.submit();
                      } else {
                          $wire.submit();
                      }
                  "
                  class="space-y-5">

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" wire:model="name"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Profile Photo --}}
                <div x-data="{
                    preview: null,
                    error: null,
                    async handleFile(e) {
                        this.error = null;
                        const file = e.target.files[0];
                        if (!file) return;
                        if (!file.type.startsWith('image/')) { this.error = 'Please choose an image file.'; return; }
                        if (file.size > 5 * 1024 * 1024) { this.error = 'Image is larger than 5MB.'; return; }
                        const reader = new FileReader();
                        reader.onload = async (ev) => {
                            this.preview = ev.target.result;
                            await $wire.set('photoData', ev.target.result, false);
                            await $wire.set('photoName', file.name, false);
                        };
                        reader.onerror = () => { this.error = 'Failed to read file.'; };
                        reader.readAsDataURL(file);
                    }
                }">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profile Photo</label>
                    <div class="flex items-center gap-4">
                        <template x-if="preview">
                            <img :src="preview" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
                        </template>
                        <template x-if="!preview">
                            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                </svg>
                            </div>
                        </template>
                        <div>
                            <input type="file" accept="image/*" @change="handleFile($event)"
                                   class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-primary-hover file:cursor-pointer">
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG or WebP. Max 5MB.</p>
                        </div>
                    </div>
                    <template x-if="error">
                        <p class="mt-1 text-xs text-red-600" x-text="error"></p>
                    </template>
                </div>

                {{-- Bio --}}
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio <span class="text-red-500">*</span></label>
                    <textarea id="bio" wire:model="bio" rows="4" maxlength="1000" placeholder="Tell us about yourself and your content..."
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm"></textarea>
                    <div class="flex justify-between mt-1">
                        @error('bio') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 ml-auto">{{ strlen($bio) }}/1000</p>
                    </div>
                </div>

                {{-- Category & Country --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category" wire:model="category"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country <span class="text-red-500">*</span></label>
                        <select id="country" wire:model="country"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            <option value="">Select country</option>
                            @foreach($africanCountries as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Contact Email --}}
                <div>
                    <label for="contactEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Email <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                    <input type="email" id="contactEmail" wire:model="contactEmail"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                    @error('contactEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Social Links --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Social Links</label>
                        <button type="button" wire:click="addSocialLink" class="text-xs text-primary hover:text-primary-hover font-medium">+ Add link</button>
                    </div>
                    <div class="space-y-2">
                        @foreach($socialLinks as $index => $link)
                            <div class="flex items-center gap-2" wire:key="social-{{ $index }}">
                                <select wire:model="socialLinks.{{ $index }}.platform"
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-xs w-28">
                                    <option value="instagram">Instagram</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="twitter">Twitter/X</option>
                                    <option value="facebook">Facebook</option>
                                    <option value="website">Website</option>
                                </select>
                                <input wire:model="socialLinks.{{ $index }}.url" type="url" placeholder="URL"
                                       class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                <input wire:model="socialLinks.{{ $index }}.handle" type="text" placeholder="Handle"
                                       class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                <button type="button" wire:click="removeSocialLink({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            @error("socialLinks.{$index}.url") <p class="text-xs text-red-600 ml-[7.5rem]">{{ $message }}</p> @enderror
                        @endforeach
                        @if(empty($socialLinks))
                            <p class="text-sm text-gray-400 dark:text-gray-500">No social links added yet.</p>
                        @endif
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit"
                            class="w-full px-6 py-3 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover transition-colors disabled:opacity-60"
                            wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading.remove wire:target="submit">Submit for Review</span>
                        <span wire:loading wire:target="submit">Submitting...</span>
                    </button>
                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Your profile will be reviewed by our team before it goes live. This usually takes 1-2 business days.</p>
                </div>
            </form>
        </div>
    </div>
</section>
