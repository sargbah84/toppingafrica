<div>
    <x-slot name="header">Content Lab</x-slot>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header / Research Now --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Content ideas are auto-researched daily at 8:00 AM Africa/Lagos.
                @if($lastResearched)
                    Last researched {{ $lastResearched->diffForHumans() }}.
                @else
                    No ideas researched yet.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs text-gray-600 dark:text-gray-400">Per niche</label>
            <input wire:model="fetchCount" type="number" min="1" max="10"
                   class="w-20 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <button type="button"
                    wire:click="fetchNow"
                    wire:loading.attr="disabled"
                    wire:target="fetchNow"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-60 transition">
                <svg wire:loading.remove wire:target="fetchNow" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                <svg wire:loading wire:target="fetchNow" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="fetchNow">Research Now</span>
                <span wire:loading wire:target="fetchNow">Researching... (may take 1-3 min)</span>
            </button>
            <button type="button"
                    @click="window.tcModal.confirm('Delete all expired and dismissed ideas? Generated ideas will be kept.', {variant:'danger', confirmText:'Cleanup'}).then(ok => ok && $wire.cleanup())"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
                Cleanup
            </button>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-x-6 overflow-x-auto" aria-label="Tabs">
            @foreach([
                'all' => 'All',
                'pending' => 'Pending',
                'generated' => 'Generated',
                'dismissed' => 'Dismissed',
                'expired' => 'Expired',
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

    {{-- Search + Niche Filter --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search ideas..."
               class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm w-64">

        <select wire:model.live="nicheFilter"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="all">All niches</option>
            @foreach($niches as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Idea</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Niche</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SEO Score</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($ideas as $idea)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition" wire:key="idea-{{ $idea->id }}">
                        {{-- Idea --}}
                        <td class="px-4 py-3 max-w-md">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $idea->title }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $idea->description }}</div>
                            @if($idea->suggested_keyword)
                                <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $idea->suggested_keyword }}
                                </span>
                            @endif
                            @if($idea->source_url)
                                <div class="text-xs text-gray-400 mt-1">
                                    via <a href="{{ $idea->source_url }}" target="_blank" rel="noopener" class="text-indigo-500 hover:underline">source</a>
                                </div>
                            @endif
                        </td>

                        {{-- Niche --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                {{ $niches[$idea->niche] ?? $idea->niche }}
                            </span>
                        </td>

                        {{-- SEO Score --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                    {{ $idea->seo_score >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($idea->seo_score >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                    {{ $idea->seo_score }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">
                                <span title="Search Interest">
                                    @if($idea->search_interest === 'high')
                                        <span class="text-green-500">&#9650;</span>
                                    @elseif($idea->search_interest === 'low')
                                        <span class="text-red-500">&#9660;</span>
                                    @else
                                        <span class="text-yellow-500">&#9654;</span>
                                    @endif
                                    Int.
                                </span>
                                <span title="Competition">
                                    @if($idea->competition === 'low')
                                        <span class="text-green-500">&#9660;</span>
                                    @elseif($idea->competition === 'high')
                                        <span class="text-red-500">&#9650;</span>
                                    @else
                                        <span class="text-yellow-500">&#9654;</span>
                                    @endif
                                    Comp.
                                </span>
                            </div>
                        </td>

                        {{-- Type --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $idea->suggested_post_type === 'listicle' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : ($idea->suggested_post_type === 'quiz' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : ($idea->suggested_post_type === 'trivia' ? 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400')) }}">
                                {{ ucfirst($idea->suggested_post_type) }}
                            </span>
                            @if($idea->source === 'trends')
                                <span class="block mt-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    Trending
                                </span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            <div>{{ $idea->researched_at->format('M j, Y') }}</div>
                            @if($idea->expires_at->isPast())
                                <div class="text-red-500">expired {{ $idea->expires_at->diffForHumans() }}</div>
                            @else
                                <div class="text-gray-400">expires {{ $idea->expires_at->diffForHumans() }}</div>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($idea->status === 'pending' && $idea->expires_at->isFuture())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Ready</span>
                            @elseif($idea->status === 'generated')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Generated</span>
                            @elseif($idea->status === 'generating')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Generating</span>
                            @elseif($idea->status === 'dismissed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Dismissed</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Expired</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                @if($idea->status === 'pending' && $idea->expires_at->isFuture())
                                    <button wire:click="generate({{ $idea->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="generate"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-60 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/>
                                        </svg>
                                        Generate
                                    </button>
                                    <button wire:click="dismiss({{ $idea->id }})"
                                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 text-xs font-semibold">
                                        Dismiss
                                    </button>
                                @elseif($idea->status === 'generated')
                                    @if($idea->generated_post_id)
                                        <a href="{{ route('admin.blog.posts.edit', $idea->generated_post_id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                            </svg>
                                            Edit Post
                                        </a>
                                    @endif
                                @elseif($idea->status === 'dismissed')
                                    <button wire:click="restoreIdea({{ $idea->id }})"
                                            class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 text-xs font-semibold">
                                        Restore
                                    </button>
                                @endif
                                <button type="button"
                                        @click="window.tcModal.confirm(@js('Delete idea: '.$idea->title.'?'), {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.delete({{ $idea->id }}))"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs font-semibold">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No content ideas found. Click <strong>Research Now</strong> to discover trending topics.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $ideas->links() }}
    </div>

    {{-- Generation Progress Modal --}}
    @if($showProgressModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true"
         x-data="{}"
         x-init="document.body.classList.add('overflow-hidden')"
         x-on:remove="document.body.classList.remove('overflow-hidden')">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-8 z-10">

                {{-- Header --}}
                <div class="text-center mb-8">
                    @if($generationStep === 'complete')
                        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                            <svg class="h-7 w-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Post Created Successfully</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Your draft post is ready to review</p>
                    @elseif($generationStep === 'failed')
                        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                            <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Generation Failed</h3>
                        <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $generationError }}</p>
                    @else
                        <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-indigo-100 dark:bg-indigo-900/30 mb-4">
                            <svg class="animate-spin h-7 w-7 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Generating Article</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This may take 30-60 seconds...</p>
                    @endif
                </div>

                {{-- Checklist Steps --}}
                <div class="space-y-4 mb-8">
                    @php
                        $steps = [
                            'researching' => ['label' => 'Preparing topic & keywords', 'icon' => 'magnifying-glass'],
                            'generating' => ['label' => 'Generating content with AI', 'icon' => 'sparkles'],
                            'saving' => ['label' => 'Creating draft post', 'icon' => 'document-plus'],
                            'complete' => ['label' => 'Post ready for review', 'icon' => 'check-badge'],
                        ];
                        $stepOrder = ['researching', 'generating', 'saving', 'complete'];
                        $currentIndex = array_search($generationStep, $stepOrder);
                        if ($currentIndex === false) $currentIndex = -1;
                    @endphp

                    @foreach($stepOrder as $index => $stepKey)
                        @php
                            $step = $steps[$stepKey];
                            $isComplete = $generationStep !== 'failed' && $index < $currentIndex;
                            $isCurrent = $stepKey === $generationStep && $generationStep !== 'complete' && $generationStep !== 'failed';
                            $isPending = $index > $currentIndex && $generationStep !== 'failed';
                            $isSuccess = $stepKey === 'complete' && $generationStep === 'complete';
                            $isFailed = $generationStep === 'failed' && $index >= $currentIndex;
                        @endphp

                        <div class="flex items-center gap-3" x-data="{ show: false }" x-init="setTimeout(() => show = true, {{ $index * 150 }})">
                            {{-- Status icon --}}
                            <div class="flex-shrink-0 transition-all duration-300"
                                 x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100">
                                @if($isComplete || $isSuccess)
                                    <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    </div>
                                @elseif($isCurrent)
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <svg class="animate-spin h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>
                                @elseif($isFailed)
                                    <div class="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <div class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-500"></div>
                                    </div>
                                @endif
                            </div>

                            {{-- Label --}}
                            <span class="text-sm font-medium transition-colors duration-300
                                {{ $isComplete || $isSuccess ? 'text-green-700 dark:text-green-400' : '' }}
                                {{ $isCurrent ? 'text-indigo-700 dark:text-indigo-400' : '' }}
                                {{ $isPending ? 'text-gray-400 dark:text-gray-500' : '' }}
                                {{ $isFailed ? 'text-red-600 dark:text-red-400' : '' }}"
                                 x-show="show" x-transition:enter="transition ease-out duration-300 delay-75" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0">
                                {{ $step['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Generated post title --}}
                @if($generationStep === 'complete' && $generatedPostTitle)
                    <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Generated Title</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $generatedPostTitle }}</p>
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="flex items-center justify-end gap-3">
                    @if($generationStep === 'complete' && $generatedPostId)
                        <button wire:click="closeProgressModal"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Close
                        </button>
                        <a href="{{ route('blog.show', $generatedPostSlug) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            Preview
                        </a>
                        <a href="{{ route('admin.blog.posts.edit', $generatedPostId) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                            </svg>
                            Edit Post
                        </a>
                    @elseif($generationStep === 'failed')
                        <button wire:click="closeProgressModal"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Close
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
