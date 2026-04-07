<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h1>
        <button
            wire:click="$toggle('showUploadPanel')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
        >
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            Upload Files
        </button>
    </div>

    {{-- Upload Panel --}}
    @if ($showUploadPanel)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Images</h2>
                <button wire:click="$set('showUploadPanel', false)" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                {{-- Collection Selector --}}
                <div>
                    <label for="upload-collection" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Collection</label>
                    <select
                        wire:model="uploadCollection"
                        id="upload-collection"
                        class="w-full sm:w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                    >
                        <option value="content_images">Content Images</option>
                        <option value="featured_image">Featured Images</option>
                        <option value="gallery">Gallery</option>
                    </select>
                </div>

                {{-- Drop Zone --}}
                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="dragging = false"
                >
                    <label
                        class="flex flex-col items-center justify-center py-12 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                        :class="dragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500'"
                    >
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 0 1 2.25-2.25h15A2.25 2.25 0 0 1 21.75 6v12A2.25 2.25 0 0 1 19.5 20.25H4.5A2.25 2.25 0 0 1 2.25 18Z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Drop images here or click to browse</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 mt-1">Max 10MB per file. Images only.</span>
                        <input wire:model="uploads" type="file" accept="image/*" multiple class="hidden" />
                    </label>
                </div>

                {{-- Upload Progress --}}
                <div wire:loading wire:target="uploads" class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Processing files...
                </div>

                {{-- Preview Uploaded Files --}}
                @if (count($uploads) > 0)
                    <div class="space-y-3">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ count($uploads) }} file(s) ready to upload</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($uploads as $upload)
                                <div class="relative">
                                    <img src="{{ $upload->temporaryUrl() }}" alt="Preview" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                                </div>
                            @endforeach
                        </div>
                        <button
                            wire:click="upload"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="upload">Upload All</span>
                            <span wire:loading wire:target="upload">
                                <svg class="animate-spin inline-block h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Uploading...
                            </span>
                        </button>
                    </div>
                @endif

                @error('uploads.*') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="p-4 flex flex-col sm:flex-row gap-4 items-center">
            <div class="relative w-full sm:w-80">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search media..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                />
            </div>
            <select
                wire:model.live="collectionFilter"
                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm py-2 px-3"
            >
                <option value="">All Collections</option>
                @foreach ($this->collections as $collection)
                    <option value="{{ $collection }}">{{ ucwords(str_replace('_', ' ', $collection)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Media Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" wire:loading.class="opacity-50">
        @forelse ($this->media as $item)
            <div
                wire:key="media-{{ $item->id }}"
                class="group relative bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border-2 transition-colors cursor-pointer
                    {{ $selectedMediaId === $item->id ? 'border-indigo-500' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600' }}"
                wire:click="selectMedia({{ $item->id }})"
            >
                <div class="aspect-square">
                    <img
                        src="{{ $item->getUrl(method_exists($item, 'hasGeneratedConversion') && $item->hasGeneratedConversion('thumbnail') ? 'thumbnail' : '') }}"
                        alt="{{ $item->name }}"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                </div>

                {{-- Overlay Actions --}}
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <div class="flex items-center gap-2">
                        <button
                            wire:click.stop="insertMedia({{ $item->id }})"
                            class="p-2 bg-white dark:bg-gray-700 rounded-full shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
                            title="Insert"
                        >
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click.stop="window.tcModal.confirm('Are you sure you want to delete this media?', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.deleteMedia({{ $item->id }}))"
                            class="p-2 bg-white dark:bg-gray-700 rounded-full shadow-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors"
                            title="Delete"
                        >
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Selection Indicator --}}
                @if ($selectedMediaId === $item->id)
                    <div class="absolute top-2 right-2">
                        <div class="w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                    </div>
                @endif

                {{-- File Name --}}
                <div class="p-2">
                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $item->name }}">{{ $item->name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ number_format($item->size / 1024, 1) }} KB</p>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 0 1 2.25-2.25h15A2.25 2.25 0 0 1 21.75 6v12A2.25 2.25 0 0 1 19.5 20.25H4.5A2.25 2.25 0 0 1 2.25 18Z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    @if ($search || $collectionFilter)
                        No media found matching your filters.
                    @else
                        No media uploaded yet. Click "Upload Files" to get started.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->media->hasPages())
        <div class="mt-6">
            {{ $this->media->links() }}
        </div>
    @endif

    {{-- Selected Media Detail Panel --}}
    @if ($selectedMediaId)
        <div class="fixed bottom-0 left-0 right-0 sm:bottom-6 sm:right-6 sm:left-auto sm:w-80 bg-white dark:bg-gray-800 shadow-xl rounded-t-lg sm:rounded-lg border border-gray-200 dark:border-gray-700 z-50">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Selected Media</h3>
                <button wire:click="clearSelection" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-4">
                @if ($selectedMediaUrl)
                    <img src="{{ $selectedMediaUrl }}" alt="{{ $selectedMediaName }}" class="w-full h-40 object-cover rounded-lg mb-3" />
                @endif
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $selectedMediaName }}</p>
                <div class="flex items-center gap-2 mt-3">
                    <button
                        wire:click="insertMedia({{ $selectedMediaId }})"
                        class="flex-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        Insert
                    </button>
                    <button
                        type="button"
                        @click="window.tcModal.confirm('Are you sure you want to delete this media?', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.deleteMedia({{ $selectedMediaId }}))"
                        class="px-3 py-1.5 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        Delete
                    </button>
                </div>
                @if ($selectedMediaUrl)
                    <div class="mt-3" x-data="{ copied: false }">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">URL</label>
                        <div class="flex items-center gap-1">
                            <input
                                type="text"
                                value="{{ $selectedMediaUrl }}"
                                readonly
                                class="flex-1 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-400"
                            />
                            <button
                                @click="navigator.clipboard.writeText('{{ $selectedMediaUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                title="Copy URL"
                            >
                                <svg x-show="!copied" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                </svg>
                                <svg x-show="copied" class="w-4 h-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
