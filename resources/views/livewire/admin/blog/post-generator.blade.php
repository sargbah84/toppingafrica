<div>
    @if($showModal)
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:init="loadModalData" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white" id="modal-title">AI Content Generator</h3>
                                <p class="text-sm text-white/80">Generate blog content using AI</p>
                            </div>
                        </div>
                        <button wire:click="closeModal" class="text-white/80 hover:text-white transition">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                    @if(!$generatedContent)
                    <!-- Generator Form -->
                    <form wire:submit="generate">
                        <!-- Title Input Mode Toggle -->
                        <div class="mb-6">
                            <div class="flex items-center space-x-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg w-fit">
                                <button type="button"
                                    wire:click="setInputMode('suggest')"
                                    class="px-4 py-2 text-sm font-medium rounded-md transition {{ $inputMode === 'suggest' ? 'bg-white dark:bg-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
                                    <svg class="w-4 h-4 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                    </svg>
                                    Trending Titles
                                </button>
                                <button type="button"
                                    wire:click="setInputMode('manual')"
                                    class="px-4 py-2 text-sm font-medium rounded-md transition {{ $inputMode === 'manual' ? 'bg-white dark:bg-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
                                    <svg class="w-4 h-4 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    Enter Manually
                                </button>
                            </div>
                        </div>

                        @if($inputMode === 'suggest')
                        <!-- Title Suggestions Section -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Niche:</label>
                                    <select wire:model="selectedNiche" wire:change="changeNiche($event.target.value)"
                                        class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($niches as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" wire:click="refreshTitles"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
                                    wire:loading.attr="disabled"
                                    wire:target="refreshTitles,loadSuggestedTitles">
                                    <span wire:loading.remove wire:target="refreshTitles,loadSuggestedTitles">
                                        <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                                        </svg>
                                        Refresh
                                    </span>
                                    <span wire:loading wire:target="refreshTitles,loadSuggestedTitles">
                                        <svg class="animate-spin w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Loading...
                                    </span>
                                </button>
                            </div>

                            <!-- Trending Keywords from Google Trends -->
                            @if(!empty($trendingKeywords))
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Trending on Google</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(array_slice($trendingKeywords, 0, 10) as $trend)
                                    @php
                                        $isRising = ($trend['type'] ?? 'top') === 'rising';
                                        $trendValue = $trend['value'] ?? 0;
                                        $formattedVal = $trend['formatted_value'] ?? '';
                                        $scoreLabel = $isRising
                                            ? (str_contains($formattedVal, '%') ? $formattedVal : ($formattedVal ?: "+{$trendValue}%"))
                                            : $trendValue;
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full
                                        {{ $isRising
                                            ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800'
                                            : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800' }}">
                                        @if($isRising)
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                        </svg>
                                        @endif
                                        {{ $trend['keyword'] ?? '' }}
                                        <span class="font-semibold {{ $isRising ? 'text-green-600 dark:text-green-400' : 'text-blue-500 dark:text-blue-400' }}">{{ $scoreLabel }}</span>
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Suggestions Grid -->
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <!-- Loading state -->
                                <div wire:loading wire:target="loadSuggestedTitles,refreshTitles,changeNiche" class="p-8 text-center">
                                    <svg class="animate-spin w-8 h-8 text-indigo-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">Loading trending title suggestions...</p>
                                </div>
                                <div wire:loading.remove wire:target="loadSuggestedTitles,refreshTitles,changeNiche">
                                @if(!empty($suggestedTitles))
                                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-64 overflow-y-auto">
                                    @foreach($suggestedTitles as $index => $suggestion)
                                    @php
                                        $isUsed = in_array(strtolower(trim($suggestion['title'])), $usedTitles, true);
                                        $titleLower = strtolower($suggestion['title'] ?? '');
                                        $matchedTrend = null;
                                        foreach ($trendingKeywords as $tk) {
                                            $kw = strtolower($tk['keyword'] ?? '');
                                            if ($kw && str_contains($titleLower, $kw)) {
                                                if (!$matchedTrend || ($tk['value'] ?? 0) > ($matchedTrend['value'] ?? 0)) {
                                                    $matchedTrend = $tk;
                                                }
                                            }
                                        }
                                    @endphp
                                    <button type="button"
                                        wire:click="selectTitle('{{ addslashes($suggestion['title']) }}')"
                                        class="w-full px-4 py-3 text-left transition group {{ $isUsed ? 'bg-gray-50 dark:bg-gray-800 opacity-60' : 'hover:bg-indigo-50 dark:hover:bg-indigo-900/20' }}">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 pr-4">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p class="font-medium {{ $isUsed ? 'text-gray-500 dark:text-gray-500 line-through' : 'text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition">
                                                        {{ $suggestion['title'] }}
                                                    </p>
                                                    @if($isUsed)
                                                    <span class="text-xs px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full font-medium">
                                                        <svg class="w-3 h-3 inline-block mr-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                        </svg>
                                                        Used
                                                    </span>
                                                    @endif
                                                    @if($matchedTrend)
                                                    @php
                                                        $mtIsRising = ($matchedTrend['type'] ?? 'top') === 'rising';
                                                        $mtFormatted = $matchedTrend['formatted_value'] ?? '';
                                                        $mtScore = $mtIsRising
                                                            ? (str_contains($mtFormatted, '%') ? $mtFormatted : ($mtFormatted ?: '+' . ($matchedTrend['value'] ?? 0) . '%'))
                                                            : ($matchedTrend['value'] ?? 0);
                                                    @endphp
                                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium inline-flex items-center gap-1
                                                        {{ $mtIsRising ? 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' }}">
                                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                                        </svg>
                                                        {{ $mtScore }}
                                                    </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                                        {{ $suggestion['category'] ?? 'General' }}
                                                    </span>
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                                        {{ ucfirst($suggestion['type'] ?? 'article') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 {{ $isUsed ? 'text-gray-300 dark:text-gray-600' : 'text-gray-300 dark:text-gray-600 group-hover:text-indigo-500 dark:group-hover:text-indigo-400' }} mt-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </div>
                                    </button>
                                    @endforeach
                                </div>
                                @else
                                <div class="p-8 text-center">
                                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 mb-3">No suggestions loaded yet</p>
                                    <button type="button" wire:click="loadSuggestedTitles"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                                        <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                                        </svg>
                                        Load Suggestions
                                    </button>
                                </div>
                                @endif
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <svg class="w-3.5 h-3.5 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                Click on a title to select it, or switch to manual input mode
                            </p>
                        </div>

                        <!-- Selected Title Display -->
                        @if($topic)
                        <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Selected Title</label>
                                    <p class="text-gray-900 dark:text-white font-medium mt-1">{{ $topic }}</p>
                                </div>
                                <button type="button" wire:click="$set('topic', '')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endif

                        @else
                        <!-- Manual Topic Input -->
                        <div class="md:col-span-2 mb-6">
                            <label for="topic" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Topic / Title</label>
                            <input type="text" id="topic" wire:model="topic"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter the main topic or title for your blog post...">
                            @error('topic') <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Keywords -->
                            <div class="md:col-span-2">
                                <label for="targetKeyword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Target Keyword (optional)</label>
                                <input type="text" id="targetKeyword" wire:model="targetKeyword"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Enter a focus keyword for SEO...">
                            </div>

                            <!-- AI Provider -->
                            <div>
                                <label for="aiProvider" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">AI Provider</label>
                                <select id="aiProvider" wire:model="aiProvider"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($providers as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Article Length -->
                            <div>
                                <label for="length" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Article Length</label>
                                <select id="length" wire:model="length"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($lengths as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Tone -->
                            <div>
                                <label for="tone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Writing Tone</label>
                                <select id="tone" wire:model="tone"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($tones as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category (optional)</label>
                                <select id="category" wire:model="categoryId"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select a category...</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Error Message -->
                        @if($error)
                        <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400">
                            <svg class="w-5 h-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            {{ $error }}
                        </div>
                        @endif

                        <!-- Generate Button -->
                        <div class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                Generation may take 30-60 seconds
                            </div>
                            <div class="flex items-center space-x-3">
                                <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-semibold rounded-lg hover:from-indigo-600 hover:to-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    wire:loading.attr="disabled"
                                    wire:target="generate"
                                    {{ empty($topic) ? 'disabled' : '' }}>
                                    <span wire:loading.remove wire:target="generate">
                                        <svg class="w-5 h-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                                        </svg>
                                        Generate Content
                                    </span>
                                    <span wire:loading wire:target="generate">
                                        <svg class="animate-spin w-5 h-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Generating...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                    @else
                    <!-- Generated Content Preview -->
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Generated Content Preview</h3>
                            <button wire:click="clearContent"
                                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition text-sm">
                                <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                                </svg>
                                Back to Generator
                            </button>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 space-y-6">
                            <!-- Title -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Title</label>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $generatedContent['title'] ?? '' }}</p>
                            </div>

                            <!-- Excerpt -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Excerpt</label>
                                <p class="text-gray-700 dark:text-gray-300">{{ $generatedContent['excerpt'] ?? '' }}</p>
                            </div>

                            <!-- SEO Data -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Meta Title</label>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $generatedContent['meta_title'] ?? '' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Focus Keyword</label>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $generatedContent['focus_keyword'] ?? '' }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Meta Description</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $generatedContent['meta_description'] ?? '' }}</p>
                            </div>

                            <!-- Tags -->
                            @if(!empty($generatedContent['suggested_tags']))
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Suggested Tags</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($generatedContent['suggested_tags'] as $tag)
                                    <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-full">#{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Content Preview -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Content Preview</label>
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto prose prose-sm dark:prose-invert">
                                    {!! Str::limit($generatedContent['content'] ?? '', 1500) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <button wire:click="clearContent"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-lg transition">
                                <svg class="w-4 h-4 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                                </svg>
                                Regenerate
                            </button>
                            <button wire:click="useContent"
                                class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Use This Content
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
