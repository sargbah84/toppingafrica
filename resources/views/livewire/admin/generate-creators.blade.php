<div>
    <x-slot name="header">Generate Creators</x-slot>

    <div class="{{ !empty($previewCreators) ? 'max-w-5xl' : 'max-w-2xl' }}">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Discover African Creators</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Use AI to discover real African creators by niche and country. Creators will appear in the pending queue for review before going live. Enable <strong>Dry run</strong> to preview results without saving anything.
            </p>

            <form wire:submit="generate" class="space-y-4">
                {{-- Niche --}}
                <div>
                    <label for="niche" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Niche / Category</label>
                    <select wire:model="niche" id="niche"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select a niche...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('niche') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Country --}}
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                    <input wire:model="country" type="text" id="country" placeholder="e.g. Nigeria, Ghana, Kenya..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('country') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Dry Run --}}
                <div class="flex items-start gap-2 pt-1">
                    <input wire:model="dryRun" type="checkbox" id="dryRun"
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                    <label for="dryRun" class="text-sm text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Dry run</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Preview what would be created without saving to the database. Still calls Perplexity, Claude, and Wikimedia (API spend applies).</span>
                    </label>
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="generate"
                            class="inline-flex items-center px-4 py-2 {{ $dryRun ? 'bg-amber-600 hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:ring-amber-500' : 'bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:ring-indigo-500' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading wire:target="generate">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="generate">{{ $dryRun ? 'Preview (Dry Run)' : 'Generate Creators' }}</span>
                        <span wire:loading wire:target="generate">Discovering creators...</span>
                    </button>
                </div>
            </form>

            {{-- Status Message --}}
            @if($statusMessage)
                <div class="mt-4 p-3 rounded-md {{ $generatedCount > 0 ? ($dryRun ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400') : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' }} text-sm">
                    {{ $statusMessage }}
                </div>
            @endif

            {{-- Dry-run preview --}}
            @if(!empty($previewCreators))
                <div class="mt-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                            Dry run — not saved
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ count($previewCreators) }} creator(s) would be created.
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Country</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bio</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Socials</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($previewCreators as $c)
                                    <tr>
                                        <td class="px-3 py-2 align-top">
                                            @if($c['profile_image_url'])
                                                <img src="{{ $c['profile_image_url'] }}" alt="" class="w-10 h-10 rounded-full object-cover">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-400">—</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $c['name'] }}</div>
                                            @if($c['duplicate'])
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 mt-1">
                                                    Already exists — would be skipped
                                                </span>
                                            @endif
                                            @if($c['contact_email'])
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $c['contact_email'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $c['country'] }}</td>
                                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $c['category'] }}</td>
                                        <td class="px-3 py-2 align-top text-gray-600 dark:text-gray-300 max-w-md">
                                            <div class="line-clamp-3">{{ $c['bio'] }}</div>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            @if(empty($c['socials']))
                                                <span class="text-xs text-gray-400">—</span>
                                            @else
                                                <ul class="space-y-0.5 text-xs">
                                                    @foreach($c['socials'] as $s)
                                                        <li class="text-gray-600 dark:text-gray-300">
                                                            <span class="font-medium capitalize">{{ $s['platform'] }}:</span>
                                                            {{ $s['handle'] ?? $s['url'] }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Happy with these? Uncheck <strong>Dry run</strong> and run again to save them to the pending queue.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
