<div>
    {{-- Edit toggle button — shown in the top-right of the profile card --}}
    @if(! $editing)
        <button wire:click="startEditing" type="button"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gray-900 text-sm font-semibold text-white hover:bg-gray-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
            Quick Edit
        </button>
    @else
        {{-- Full-page edit overlay --}}
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" wire:click="cancelEditing"></div>
            <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10"
                 x-data="{
                     photoPreview: null,
                     handlePhoto(event) {
                         const file = event.target.files[0];
                         if (!file) return;
                         if (file.size > 5 * 1024 * 1024) {
                             alert('Image must be under 5MB');
                             return;
                         }
                         const reader = new FileReader();
                         reader.onload = (e) => {
                             this.photoPreview = e.target.result;
                             $wire.set('photoData', e.target.result);
                             $wire.set('photoName', file.name);
                         };
                         reader.readAsDataURL(file);
                     }
                 }">

                {{-- Header --}}
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Creator Profile</h3>
                    <div class="flex items-center gap-2">
                        <button wire:click="cancelEditing" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button wire:click="save" type="button"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg hover:bg-primary-hover transition disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Save Changes</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Profile Photo --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Profile Photo</label>
                        <div class="flex items-center gap-4">
                            <div class="relative group cursor-pointer" @click="$refs.photoInput.click()">
                                <div class="w-24 h-24 rounded-full overflow-hidden ring-2 ring-gray-200 dark:ring-gray-600">
                                    <template x-if="photoPreview">
                                        <img :src="photoPreview" class="w-full h-full object-cover" alt="Preview">
                                    </template>
                                    <template x-if="!photoPreview">
                                        @if($creator->getFirstMediaUrl('profile_image'))
                                            <img src="{{ $creator->getFirstMediaUrl('profile_image') }}" alt="{{ $creator->name }}"
                                                 class="w-full h-full object-cover">
                                        @elseif($creator->getRawOriginal('profile_image_url'))
                                            <img src="{{ $creator->getRawOriginal('profile_image_url') }}" alt="{{ $creator->name }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-white text-2xl font-bold"
                                                 style="background-color: {{ $creator->avatar_color }}">
                                                {{ $creator->initials }}
                                            </div>
                                        @endif
                                    </template>
                                </div>
                                {{-- Hover overlay --}}
                                <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/>
                                    </svg>
                                </div>
                                <input type="file" x-ref="photoInput" class="hidden" accept="image/*" @change="handlePhoto($event)">
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <p>Click to upload a new photo</p>
                                <p class="text-xs mt-1">JPG, PNG, WebP. Max 5MB.</p>
                            </div>
                        </div>

                        {{-- Google Images picker (Serper) --}}
                        <div class="mt-4">
                            @if(! $showAvatarPicker)
                                <button type="button" wire:click="openAvatarPicker"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-primary bg-primary/10 rounded-full hover:bg-primary/20 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    Search Google Images
                                </button>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Find a portrait online and save it as your profile photo.</p>
                            @else
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3">
                                    <div class="flex items-center gap-2">
                                        <input wire:model="avatarSearchQuery" type="text"
                                               wire:keydown.enter.prevent="searchAvatarFromGoogle"
                                               placeholder="e.g. {{ $editName }} {{ $editCountry }} portrait"
                                               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:border-primary focus:ring-primary">
                                        <button type="button" wire:click="searchAvatarFromGoogle"
                                                wire:loading.attr="disabled" wire:target="searchAvatarFromGoogle"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-primary text-white rounded-lg hover:bg-primary-hover disabled:opacity-50 transition">
                                            <span wire:loading.remove wire:target="searchAvatarFromGoogle">Search</span>
                                            <span wire:loading wire:target="searchAvatarFromGoogle">Searching...</span>
                                        </button>
                                        <button type="button" wire:click="closeAvatarPicker"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    @if($avatarPickerError)
                                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $avatarPickerError }}</p>
                                    @endif

                                    @if(! empty($avatarCandidates))
                                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Click an image to set it as your profile photo.</p>
                                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-2">
                                            @foreach($avatarCandidates as $i => $cand)
                                                <button type="button"
                                                        wire:click="pickAvatarCandidate({{ $i }})"
                                                        wire:loading.attr="disabled" wire:target="pickAvatarCandidate"
                                                        class="relative aspect-square rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 hover:border-primary hover:ring-2 hover:ring-primary/20 transition group disabled:opacity-50 disabled:cursor-wait">
                                                    <img src="{{ $cand['thumb'] }}" alt="{{ $cand['title'] }}" class="w-full h-full object-cover" loading="lazy" referrerpolicy="no-referrer">
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition"></div>
                                                </button>
                                            @endforeach
                                        </div>
                                        <div wire:loading wire:target="pickAvatarCandidate" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Saving image...
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="editName" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" id="editName" wire:model="editName"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                        @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Bio --}}
                    <div>
                        <label for="editBio" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Bio</label>
                        <textarea id="editBio" wire:model="editBio" rows="4"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                        <p class="mt-1 text-xs text-gray-400">{{ strlen($editBio) }}/2000</p>
                        @error('editBio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category & Country (side-by-side) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="editCategory" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select id="editCategory" wire:model="editCategory"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('editCategory') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="editCountry" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Country</label>
                            <select id="editCountry" wire:model="editCountry"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                <option value="">Select country</option>
                                @foreach($africanCountries as $country)
                                    <option value="{{ $country }}">{{ $country }}</option>
                                @endforeach
                            </select>
                            @error('editCountry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Social Links --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Social Links</label>
                            @if(count($editSocialLinks) < 6)
                                <button wire:click="addSocialLink" type="button"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-primary-hover transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Add Link
                                </button>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @foreach($editSocialLinks as $index => $link)
                                <div class="flex items-start gap-2" wire:key="social-link-{{ $index }}">
                                    <select wire:model="editSocialLinks.{{ $index }}.platform"
                                            class="w-32 shrink-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                        <option value="instagram">Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="twitter">X/Twitter</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="website">Website</option>
                                    </select>
                                    <input type="url" wire:model="editSocialLinks.{{ $index }}.url"
                                           placeholder="https://..."
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                    <input type="number" min="0" wire:model="editSocialLinks.{{ $index }}.follower_count"
                                           placeholder="Followers"
                                           class="w-28 shrink-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                    <button wire:click="removeSocialLink({{ $index }})" type="button"
                                            class="mt-2 text-gray-400 hover:text-red-500 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                @error("editSocialLinks.{$index}.url") <p class="text-xs text-red-600 ml-34">{{ $message }}</p> @enderror
                            @endforeach

                            @if(count($editSocialLinks) === 0)
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No social links yet. Click "Add Link" to add one.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="sticky bottom-0 z-10 flex items-center justify-end gap-2 px-6 py-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="cancelEditing" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button wire:click="save" type="button"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg hover:bg-primary-hover transition disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Save Changes</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
