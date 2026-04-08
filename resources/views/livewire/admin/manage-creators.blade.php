<div>
    <x-slot name="header">Manage Creators</x-slot>

    {{-- Filter Tabs --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-x-6" aria-label="Tabs">
            @foreach([
                'all' => 'All',
                'pending' => 'Pending',
                'published' => 'Published',
                'claimed' => 'Claimed',
                'pending_edits' => 'Pending Edits',
            ] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                        class="whitespace-nowrap border-b-2 pb-3 px-1 text-sm font-medium transition {{ $filter === $key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    {{ $label }}
                    <span class="ml-1 rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ $counts[$key] }}
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Search + Actions Bar --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search creators..."
                   class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm w-64">

            <select wire:model.live="countryFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All countries</option>
                @foreach($availableCountries as $country)
                    <option value="{{ $country }}">{{ $country }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="10">10 / page</option>
                <option value="20">20 / page</option>
                <option value="50">50 / page</option>
                <option value="100">100 / page</option>
                <option value="200">200 / page</option>
            </select>

            @if(count($selected) > 0 && $filter !== 'claimed')
                <button type="button" @click="window.tcModal.confirm(@js('Approve '.count($selected).' selected creators?'), {variant:'default', confirmText:'Approve'}).then(ok => ok && $wire.bulkApprove())"
                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700 transition">
                    Approve {{ count($selected) }} selected
                </button>
            @endif

            @if(count($selected) > 0)
                <button type="button" @click="window.tcModal.confirm(@js('Re-pull '.count($selected).' selected creators from AI? This dispatches background jobs and may take a while.'), {variant:'default', confirmText:'Re-pull'}).then(ok => ok && $wire.bulkRepull())"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 transition">
                    Re-pull {{ count($selected) }} selected
                </button>
            @endif
        </div>
        <button wire:click="openGenerateModal"
                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 transition">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/>
            </svg>
            Generate New
        </button>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input wire:model.live="selectAll" type="checkbox"
                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creator</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Country</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($creators as $creator)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <input wire:model.live="selected" type="checkbox" value="{{ $creator->id }}"
                                   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($creator->profile_image_url)
                                    <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}"
                                         class="w-9 h-9 rounded-full object-cover">
                                @else
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                         style="background-color: {{ $creator->avatar_color }}">
                                        {{ $creator->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $creator->name }}</div>
                                    @if($creator->contact_email)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $creator->contact_email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $creator->country }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                                {{ $creator->category }}
                            </span>
                            @if($creator->is_rising)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Rising</span>
                            @endif
                            @if($creator->is_trending)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400">Trending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClasses = match($creator->status) {
                                    'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'claimed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    default => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                {{ ucfirst($creator->status) }}
                            </span>
                            @if($creator->pending_claim_edit)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    Edits pending
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($creator->status === 'pending')
                                    <button wire:click="approve({{ $creator->id }})"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 text-xs font-medium">
                                        Approve
                                    </button>
                                @endif
                                @if($creator->pending_claim_edit)
                                    <button wire:click="approveClaim({{ $creator->id }})"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs font-medium">
                                        Approve Claim
                                    </button>
                                @endif
                                <button wire:click="edit({{ $creator->id }})"
                                        class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 text-xs font-medium">
                                    Edit
                                </button>
                                <button type="button"
                                        @click="window.tcModal.confirm(@js('Re-pull AI data for '.$creator->name.'? This will overwrite the bio, image, and social links.'), {variant:'warning', confirmText:'Re-pull'}).then(ok => ok && $wire.repullCreator({{ $creator->id }}))"
                                        wire:loading.attr="disabled"
                                        wire:target="repullCreator({{ $creator->id }})"
                                        class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 text-xs font-medium disabled:opacity-50">
                                    <span wire:loading.remove wire:target="repullCreator({{ $creator->id }})">Re-pull</span>
                                    <span wire:loading wire:target="repullCreator({{ $creator->id }})">...</span>
                                </button>
                                <button type="button"
                                        @click="window.tcModal.confirm(@js('Delete '.$creator->name.'? This cannot be undone.'), {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.delete({{ $creator->id }}))"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs font-medium">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No creators found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($creators->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $creators->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-start justify-center min-h-screen px-4 pt-16 pb-20">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="$set('showEditModal', false)"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Creator</h3>
                    <div class="flex items-center gap-3">
                        <button type="button"
                                @click="window.tcModal.confirm(@js('Re-pull AI data for '.$editName.'? This will refresh the bio, social links, and image in the form. You can then review and save.'), {variant:'warning', confirmText:'Re-pull'}).then(ok => ok && $wire.repullIntoForm())"
                                wire:loading.attr="disabled"
                                wire:target="repullIntoForm"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-md hover:bg-purple-700 disabled:opacity-50 transition">
                            <svg wire:loading.remove wire:target="repullIntoForm" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/>
                            </svg>
                            <svg wire:loading wire:target="repullIntoForm" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="repullIntoForm">Re-pull from AI</span>
                            <span wire:loading wire:target="repullIntoForm">Re-pulling...</span>
                        </button>
                        <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="saveEdit" class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                            <input wire:model="editName" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                            <input wire:model="editCountry" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('editCountry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select wire:model="editCategory" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contact Email</label>
                            <input wire:model="editContactEmail" type="email" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('editContactEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Read-only AI-estimated followers --}}
                    <div class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-3 py-2">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Estimated Followers (AI)</div>
                        @if($editFollowerCount)
                            <div class="mt-0.5 text-sm text-gray-900 dark:text-white">
                                <span class="font-semibold">{{ \App\Support\FollowerFormat::short($editFollowerCount) }}</span>
                                @if($editFollowerPlatform)
                                    <span class="text-gray-500 dark:text-gray-400">on {{ ucfirst($editFollowerPlatform) }}</span>
                                @endif
                            </div>
                        @else
                            <div class="mt-0.5 text-sm text-gray-500 dark:text-gray-400 italic">No estimate — use Re-pull to fetch from AI.</div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio</label>
                        <textarea wire:model="editBio" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        @error('editBio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Profile Photo Upload (base64 — Livewire temp uploads are unreliable on Laragon) --}}
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
                                await $wire.set('editPhotoData', ev.target.result, false);
                                await $wire.set('editPhotoName', file.name, false);
                            };
                            reader.onerror = () => { this.error = 'Failed to read file.'; };
                            reader.readAsDataURL(file);
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profile Photo</label>
                        <div class="flex items-center gap-3">
                            @php $existingCreator = \App\Models\Creator::find($editingCreatorId); @endphp
                            <div class="w-14 h-14 rounded-full overflow-hidden flex-shrink-0">
                                <template x-if="preview">
                                    <img :src="preview" class="w-full h-full object-cover" alt="Preview">
                                </template>
                                <template x-if="!preview">
                                    <div class="w-full h-full">
                                        @if($existingCreator && $existingCreator->profile_image_url)
                                            <img src="{{ $existingCreator->profile_image_url }}" class="w-full h-full object-cover" alt="{{ $editName }}">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-white text-sm font-bold bg-gray-400">
                                                {{ strtoupper(substr($editName, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </template>
                            </div>
                            <input type="file" accept="image/*" @change="handleFile($event)"
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 file:cursor-pointer">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">JPG, PNG or WebP. Max 5MB. Uploads to S3.</p>
                        <p x-show="error" x-text="error" x-cloak class="mt-1 text-xs text-red-600"></p>
                        @error('editPhotoData') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Badges (radio — single selection) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Badge</label>
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input wire:model="editBadge" type="radio" value="" class="border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                None
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input wire:model="editBadge" type="radio" value="rising" class="border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                Rising
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input wire:model="editBadge" type="radio" value="trending" class="border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                Trending
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input wire:model="editBadge" type="radio" value="featured" class="border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                Featured
                            </label>
                        </div>
                    </div>

                    {{-- Social Links --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Social Links</label>
                            <button type="button" wire:click="addSocialLink"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">
                                + Add link
                            </button>
                        </div>
                        <div class="space-y-2">
                            @foreach($editSocialLinks as $index => $link)
                                <div class="flex items-center gap-2">
                                    <select wire:model="editSocialLinks.{{ $index }}.platform"
                                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs w-28">
                                        <option value="youtube">YouTube</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="twitter">Twitter/X</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="website">Website</option>
                                    </select>
                                    <input wire:model="editSocialLinks.{{ $index }}.url" type="url" placeholder="URL"
                                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs">
                                    <input wire:model="editSocialLinks.{{ $index }}.handle" type="text" placeholder="Handle"
                                           class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs">
                                    <button type="button" wire:click="removeSocialLink({{ $index }})"
                                            class="text-red-500 hover:text-red-700 dark:text-red-400">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showEditModal', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Generate Modal --}}
    @if($showGenerateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true"
         @if($generateBatchId && !$generateBatchFinished) wire:poll.3s="refreshBatchStatus" @endif>
        <div class="flex items-start justify-center min-h-screen px-4 pt-16 pb-20">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="$set('showGenerateModal', false)"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Discover African Creators</h3>
                    <button wire:click="$set('showGenerateModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                @if(!$generateBatchId)
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Select niches and countries to discover creators in batch. Each combination is queued as a background job — you can close this modal and check back later.
                </p>

                <form wire:submit="generate" class="space-y-5">
                    {{-- Niches --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Niches</label>
                            <button type="button" wire:click="toggleAllNiches" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">
                                {{ count($generateNiches) === count($categories) ? 'Deselect all' : 'Select all' }}
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            @foreach($categories as $cat)
                                <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                                    <input wire:model="generateNiches" type="checkbox" value="{{ $cat }}"
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $cat }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('generateNiches') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Countries --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Countries</label>
                            <button type="button" wire:click="toggleAllCountries" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">
                                {{ count($generateCountries) === count($africanCountries) ? 'Deselect all' : 'Select all' }}
                            </button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach($africanCountries as $country)
                                <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                                    <input wire:model="generateCountries" type="checkbox" value="{{ $country }}"
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $country }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('generateCountries') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Selection summary --}}
                    @php $totalCombinations = count($generateNiches) * count($generateCountries); @endphp
                    @if($totalCombinations > 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ count($generateNiches) }} niche(s) x {{ count($generateCountries) }} country/countries = {{ $totalCombinations }} batch(es) to search
                        </p>
                    @endif

                    {{-- Run mode --}}
                    <div class="rounded-md bg-gray-50 dark:bg-gray-900/50 p-3 border border-gray-200 dark:border-gray-700">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input wire:model.live="useQueue" type="checkbox" class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs">
                                <span class="block font-semibold text-gray-700 dark:text-gray-300">Run in background (recommended for large batches)</span>
                                <span class="block text-gray-500 dark:text-gray-400 mt-0.5">
                                    Queues each combination as a background job. You can close this modal and check back later. Requires the queue worker to be running.
                                </span>
                            </span>
                        </label>
                        @if(!$useQueue && $totalCombinations > 5)
                            <p class="mt-2 text-xs text-yellow-700 dark:text-yellow-400">
                                ⚠ Running {{ $totalCombinations }} combinations synchronously may time out. Consider enabling background mode.
                            </p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" wire:loading.attr="disabled" wire:target="generate"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading wire:target="generate">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="generate">{{ $useQueue ? 'Queue Generation' : 'Generate Now' }}</span>
                            <span wire:loading wire:target="generate">{{ $useQueue ? 'Queueing...' : 'Discovering...' }}</span>
                        </button>
                        <button type="button" wire:click="$set('showGenerateModal', false)"
                                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            Cancel
                        </button>
                    </div>
                </form>

                {{-- Synchronous status --}}
                @if($generateStatus)
                    <div class="mt-4 p-3 rounded-md text-sm {{ !$generating && $generatedCount > 0 ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : ($generating ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400') }}">
                        {{ $generateStatus }}
                        @if($generating && $generateTotal > 0)
                            <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ ($generateProgress / $generateTotal) * 100 }}%"></div>
                            </div>
                        @endif
                    </div>
                @endif

                @else
                {{-- Queued batch progress view --}}
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $generateBatchFinished ? 'Generation complete' : 'Generation in progress...' }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->batchProcessed }} / {{ $generateBatchTotal }} batches
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ $this->batchProgressPercent }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $this->batchProgressPercent }}% complete</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-3">
                            <p class="text-xs text-green-700 dark:text-green-400 font-medium">Creators added so far</p>
                            <p class="text-2xl font-bold text-green-800 dark:text-green-300">{{ $this->creatorsAdded }}</p>
                        </div>
                        <div class="rounded-md {{ $generateBatchFailed > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-900/30' }} p-3">
                            <p class="text-xs {{ $generateBatchFailed > 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} font-medium">Failed jobs</p>
                            <p class="text-2xl font-bold {{ $generateBatchFailed > 0 ? 'text-red-800 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">{{ $generateBatchFailed }}</p>
                        </div>
                    </div>

                    @if($generateBatchFinished)
                        <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-700 dark:text-green-400">
                            All batches finished. {{ $this->creatorsAdded }} new creators added to the pending queue.
                        </div>
                    @else
                        <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-3 text-xs text-blue-700 dark:text-blue-400">
                            You can safely close this modal. Progress is saved and will continue in the background. Make sure your queue worker is running (<code class="font-mono">php artisan queue:work</code>).
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        @if($generateBatchFinished)
                            <button type="button" wire:click="clearBatch"
                                    class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-indigo-700">
                                Run Another
                            </button>
                        @else
                            <span class="text-xs text-gray-400">Auto-refreshing every 3s...</span>
                        @endif
                        <button type="button" wire:click="$set('showGenerateModal', false)"
                                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            Close
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
