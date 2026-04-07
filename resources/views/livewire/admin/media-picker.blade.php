@php /** @var \Illuminate\Support\Collection<int, \App\Models\MediaLibraryItem> $libraryImages */ @endphp

<div>
    @if ($showModal)
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 z-[999] bg-black/50 transition-opacity"
            wire:click="closeModal"
        ></div>

        {{-- Modal --}}
        <div class="fixed inset-0 z-[1000] flex items-center justify-center p-4"
             x-on:keydown.escape.window="$wire.closeModal()"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col" wire:click.stop>
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Media Picker</h2>
                    <button wire:click="closeModal" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700 px-6">
                    @foreach ([
                        'library' => 'Media Library',
                        'upload' => 'Upload',
                        'pexels' => 'Pexels',
                        'google' => 'Google Images',
                    ] as $tab => $label)
                        <button
                            wire:click="setTab('{{ $tab }}')"
                            class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px
                                {{ $activeTab === $tab
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Tab Content --}}
                <div class="flex-1 overflow-y-auto p-6 min-h-0">

                    {{-- Downloading overlay --}}
                    @if ($isDownloading)
                        <div class="absolute inset-0 z-10 bg-white/70 dark:bg-gray-800/70 flex items-center justify-center rounded-xl">
                            <div class="flex items-center gap-3 text-indigo-600 dark:text-indigo-400">
                                <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="text-sm font-medium">Downloading image to server...</span>
                            </div>
                        </div>
                    @endif

                    {{-- Tab: Media Library --}}
                    @if ($activeTab === 'library')
                        <div class="space-y-4">
                            <div>
                                <input
                                    wire:model.live.debounce.300ms="librarySearch"
                                    type="text"
                                    placeholder="Search your media library..."
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                />
                            </div>

                            @if ($libraryImages->isEmpty())
                                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                    </svg>
                                    <p class="text-sm">No images found in your library.</p>
                                    <p class="text-xs mt-1">Upload images or import from Pexels / Google to build your library.</p>
                                </div>
                            @else
                                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach ($libraryImages as $image)
                                        <button
                                            wire:click="selectImage('{{ $image->getUrl() }}')"
                                            class="group relative aspect-square rounded-lg overflow-hidden border-2 transition-all
                                                {{ $selectedImageUrl === $image->getUrl()
                                                    ? 'border-indigo-500 ring-2 ring-indigo-500/30'
                                                    : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600' }}"
                                        >
                                            <img src="{{ $image->getUrl() }}" alt="{{ $image->file_name }}" class="w-full h-full object-cover" loading="lazy" />

                                            {{-- Selected checkmark --}}
                                            @if ($selectedImageUrl === $image->getUrl())
                                                <div class="absolute inset-0 bg-indigo-500/20 flex items-center justify-center">
                                                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Filename tooltip --}}
                                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <p class="text-xs text-white truncate">{{ $image->file_name }}</p>
                                            </div>

                                            {{-- Source badge --}}
                                            @if ($image->getCustomProperty('source') && $image->getCustomProperty('source') !== 'upload')
                                                <span class="absolute top-1 right-1 px-1.5 py-0.5 text-[10px] font-medium bg-black/50 text-white rounded">
                                                    {{ ucfirst($image->getCustomProperty('source')) }}
                                                </span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Load More --}}
                                @if ($libraryImages->count() < $libraryTotal)
                                    <div class="text-center pt-2">
                                        <button
                                            wire:click="loadMoreLibrary"
                                            class="px-6 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 rounded-lg transition-colors"
                                        >
                                            <span wire:loading.remove wire:target="loadMoreLibrary">Load More ({{ $libraryTotal - $libraryImages->count() }} remaining)</span>
                                            <span wire:loading wire:target="loadMoreLibrary">Loading...</span>
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- Tab: Upload (direct fetch to /admin/upload-image) --}}
                    @if ($activeTab === 'upload')
                        <div class="space-y-4" id="media-upload-tab">
                            <div id="media-upload-zone"
                                 class="flex flex-col items-center justify-center py-16 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl transition-colors cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500">
                                <label class="cursor-pointer text-center">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Drag & drop images here, or <span class="text-indigo-600 dark:text-indigo-400 underline">browse</span>
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">PNG, JPG, GIF, WebP up to 5MB</p>
                                    <input id="media-upload-input" type="file" accept="image/*" multiple class="hidden" />
                                </label>
                            </div>

                            {{-- JS-rendered preview and upload area --}}
                            <div id="media-upload-previews"></div>
                            <div id="media-upload-btn-wrap" class="hidden">
                                <button id="media-upload-btn" type="button"
                                    class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                                    Upload to S3
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Tab: Pexels --}}
                    @if ($activeTab === 'pexels')
                        <div class="space-y-4">
                            <form wire:submit="searchPexels" class="flex gap-2">
                                <input
                                    wire:model="searchQuery"
                                    type="text"
                                    placeholder="Search free stock photos on Pexels..."
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                />
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="searchPexels"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="searchPexels">Search</span>
                                    <span wire:loading wire:target="searchPexels">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </form>

                            @if ($apiErrors['pexels'])
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-400">{{ $apiErrors['pexels'] }}</p>
                                    <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-1">Add <code class="bg-yellow-100 dark:bg-yellow-900/40 px-1 rounded">PEXELS_API_KEY</code> to your .env file. Get a free key at <a href="https://www.pexels.com/api/" target="_blank" class="underline">pexels.com/api</a>.</p>
                                </div>
                            @endif

                            @if (count($pexelsResults) > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($pexelsTotalResults) }} results found.
                                    Photos provided by <a href="https://www.pexels.com" target="_blank" class="underline text-indigo-600 dark:text-indigo-400">Pexels</a>.
                                </p>

                                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach ($pexelsResults as $photo)
                                        <div class="group relative aspect-square rounded-lg overflow-hidden">
                                            <button
                                                wire:click="downloadAndSelect('{{ $photo['url'] }}', 'Photo by {{ addslashes($photo['photographer']) }} on Pexels')"
                                                class="w-full h-full border-2 rounded-lg overflow-hidden transition-all
                                                    {{ $selectedImageUrl !== null && $selectedImageUrl === ($photo['s3_url'] ?? '') ? 'border-indigo-500 ring-2 ring-indigo-500/30' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600' }}"
                                            >
                                                <img src="{{ $photo['thumb'] }}" alt="{{ $photo['alt'] }}" class="w-full h-full object-cover" loading="lazy" />
                                            </button>

                                            {{-- Attribution --}}
                                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                                <p class="text-[10px] text-white">
                                                    by <span class="font-medium">{{ $photo['photographer'] }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($pexelsTotalResults > count($pexelsResults))
                                    <div class="text-center pt-2">
                                        <button
                                            wire:click="loadMorePexels"
                                            wire:loading.attr="disabled"
                                            wire:target="loadMorePexels"
                                            class="px-6 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="loadMorePexels">Load More</span>
                                            <span wire:loading wire:target="loadMorePexels">Loading...</span>
                                        </button>
                                    </div>
                                @endif
                            @elseif (trim($searchQuery) !== '' && ! $apiErrors['pexels'])
                                <div wire:loading.remove wire:target="searchPexels" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <p class="text-sm">No results found for "{{ $searchQuery }}".</p>
                                </div>
                            @else
                                <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    <p class="text-sm">Search for free stock photos from Pexels.</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Tab: Google Images --}}
                    @if ($activeTab === 'google')
                        <div class="space-y-4">
                            <form wire:submit="searchGoogle" class="flex gap-2">
                                <input
                                    wire:model="searchQuery"
                                    type="text"
                                    placeholder="Search images via Google..."
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                />
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="searchGoogle"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="searchGoogle">Search</span>
                                    <span wire:loading wire:target="searchGoogle">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </form>

                            @if ($apiErrors['google'])
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-400">{{ $apiErrors['google'] }}</p>
                                    <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-1">
                                        Add <code class="bg-yellow-100 dark:bg-yellow-900/40 px-1 rounded">SERPER_API_KEY</code> to your .env file.
                                        Get a free key at <a href="https://serper.dev/" target="_blank" class="underline">serper.dev</a> (2,500 free searches/month).
                                    </p>
                                </div>
                            @endif

                            @if (count($googleResults) > 0)
                                <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach ($googleResults as $image)
                                        <div class="group relative aspect-square rounded-lg overflow-hidden">
                                            <button
                                                wire:click="downloadAndSelect('{{ $image['url'] }}', 'Source: {{ addslashes($image['context_url'] ?? '') }}')"
                                                class="w-full h-full border-2 rounded-lg overflow-hidden transition-all border-transparent hover:border-gray-300 dark:hover:border-gray-600"
                                            >
                                                <img src="{{ $image['thumb'] }}" alt="{{ $image['title'] }}" class="w-full h-full object-cover" loading="lazy" />
                                            </button>

                                            {{-- Title --}}
                                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                                <p class="text-[10px] text-white truncate">{{ $image['title'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($googleTotalResults > count($googleResults))
                                    <div class="text-center pt-2">
                                        <button
                                            wire:click="loadMoreGoogle"
                                            wire:loading.attr="disabled"
                                            wire:target="loadMoreGoogle"
                                            class="px-6 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="loadMoreGoogle">Load More</span>
                                            <span wire:loading wire:target="loadMoreGoogle">Loading...</span>
                                        </button>
                                    </div>
                                @endif
                            @elseif (trim($searchQuery) !== '' && ! $apiErrors['google'])
                                <div wire:loading.remove wire:target="searchGoogle" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <p class="text-sm">No results found for "{{ $searchQuery }}".</p>
                                </div>
                            @else
                                <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    <p class="text-sm">Search for images via Google Custom Search.</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Bottom bar: selected image + insert button --}}
                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 rounded-b-xl">
                    <div class="flex items-center gap-3">
                        @if ($selectedImageUrl)
                            <img src="{{ $selectedImageUrl }}" alt="Selected" class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Image selected</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[300px]">{{ $selectedImageUrl }}</p>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No image selected. Click an image or upload one to continue.</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            wire:click="closeModal"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="insertSelected"
                            @disabled(! $selectedImageUrl)
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Insert Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    const wire = $wire;
    let pendingFiles = [];

    function setupUploadZone() {
        const zone = document.getElementById('media-upload-zone');
        const input = document.getElementById('media-upload-input');
        const btn = document.getElementById('media-upload-btn');
        const btnWrap = document.getElementById('media-upload-btn-wrap');
        const previews = document.getElementById('media-upload-previews');
        if (!zone || !input) return;
        if (zone.dataset.init) return;
        zone.dataset.init = '1';

        function renderPreviews() {
            if (!previews) return;
            if (pendingFiles.length === 0) {
                previews.innerHTML = '';
                if (btnWrap) btnWrap.classList.add('hidden');
                return;
            }
            if (btnWrap) btnWrap.classList.remove('hidden');
            if (btn) btn.textContent = 'Upload ' + pendingFiles.length + ' image' + (pendingFiles.length > 1 ? 's' : '') + ' to S3';
            previews.innerHTML = pendingFiles.map(function(f, i) {
                return '<div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">' +
                    '<img src="' + f.preview + '" class="w-16 h-16 object-cover rounded-lg" />' +
                    '<div class="flex-1 min-w-0">' +
                    '<p class="text-sm font-medium text-gray-900 dark:text-white truncate">' + f.name + '</p>' +
                    '<p class="text-xs text-gray-500 dark:text-gray-400">' + (f.size / 1024).toFixed(1) + ' KB</p>' +
                    '</div>' +
                    '<button onclick="window._mediaRemoveFile(' + i + ')" class="text-gray-400 hover:text-red-500">' +
                    '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
                    '</button></div>';
            }).join('');
        }

        window._mediaRemoveFile = function(index) {
            pendingFiles.splice(index, 1);
            renderPreviews();
        };

        function addFiles(files) {
            var maxSize = 5 * 1024 * 1024;
            Array.from(files).forEach(function(file) {
                if (!file.type.startsWith('image/')) return;
                if (file.size > maxSize) { window.tcModal && window.tcModal.alert(file.name + ' is too large (max 5MB)', {variant:'warning'}); return; }
                var reader = new FileReader();
                reader.onload = function(e) {
                    pendingFiles.push({ file: file, name: file.name, size: file.size, type: file.type, preview: e.target.result });
                    renderPreviews();
                };
                reader.readAsDataURL(file);
            });
        }

        input.addEventListener('change', function(e) { addFiles(e.target.files); e.target.value = ''; });
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('border-indigo-500'); });
        zone.addEventListener('dragleave', function(e) { e.preventDefault(); zone.classList.remove('border-indigo-500'); });
        zone.addEventListener('drop', function(e) { e.preventDefault(); zone.classList.remove('border-indigo-500'); addFiles(e.dataTransfer.files); });

        if (btn) {
            btn.addEventListener('click', async function() {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Uploading...';
                var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                for (var i = 0; i < pendingFiles.length; i++) {
                    var f = pendingFiles[i];
                    var formData = new FormData();
                    formData.append('image', f.file);
                    try {
                        var resp = await fetch('/admin/upload-image', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: formData });
                        var data = await resp.json();
                        if (!resp.ok) throw new Error(data.error || data.message || 'Upload failed with status ' + resp.status);
                        wire.call('onUploadComplete', data.url, f.name, f.type, f.size);
                    } catch (err) {
                        window.tcModal && window.tcModal.alert('Failed to upload ' + f.name + ': ' + err.message, {variant:'danger', title:'Upload failed'});
                    }
                }
                pendingFiles = [];
                renderPreviews();
                btn.disabled = false;
                btn.textContent = 'Upload to S3';
            });
        }
    }

    // Watch for DOM changes to re-init when Upload tab appears
    var obs = new MutationObserver(function() { setTimeout(setupUploadZone, 50); });
    obs.observe(wire.el, { childList: true, subtree: true });
    setTimeout(setupUploadZone, 100);
</script>
@endscript
