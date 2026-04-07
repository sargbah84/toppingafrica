<div>
    <x-slot name="header">Generate Creators</x-slot>

    <div class="max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Discover African Creators</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Use AI to discover real content creators by niche and country. Creators will appear in the pending queue for review before going live.
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

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="generate"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading wire:target="generate">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="generate">Generate Creators</span>
                        <span wire:loading wire:target="generate">Discovering creators...</span>
                    </button>
                </div>
            </form>

            {{-- Status Message --}}
            @if($statusMessage)
                <div class="mt-4 p-3 rounded-md {{ $generatedCount > 0 ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' }} text-sm">
                    {{ $statusMessage }}
                </div>
            @endif
        </div>
    </div>
</div>
