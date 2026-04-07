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
                Hi {{ $creator->name }}! Update your profile below. Changes will be reviewed by our team before going live.
            </p>

            <form action="{{ url('/creators/claim/' . $creator->claim_token) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

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
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary-hover transition-colors">
                        Submit for Review
                    </button>
                    <p class="mt-2 text-xs text-gray-400">Your changes will be reviewed by our team before going live.</p>
                </div>
            </form>
        </div>
    </div>
</section>

</x-layouts.blog>
