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
            <button type="button"
                    wire:click="openSettings"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                Settings
            </button>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-x-6 overflow-x-auto" aria-label="Tabs">
            @foreach([
                'all' => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
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
                            <div wire:click="viewIdea({{ $idea->id }})" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition">{{ $idea->title }}</div>
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
                            @if($idea->status === 'approved')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Approved</span>
                            @elseif($idea->status === 'pending' && $idea->expires_at->isFuture())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Ready</span>
                            @elseif($idea->status === 'generated')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Generated</span>
                            @elseif($idea->status === 'generating')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Generating...
                                </span>
                            @elseif($idea->generation_error)
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                    <p class="text-xs text-red-500 mt-0.5 max-w-[200px] truncate" title="{{ $idea->generation_error }}">{{ Str::limit($idea->generation_error, 60) }}</p>
                                </div>
                            @elseif($idea->status === 'dismissed')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    Dismissed
                                </span>
                                @if($idea->dismissal_reason)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ \App\Livewire\Admin\Blog\ContentLab::DISMISSAL_REASONS[$idea->dismissal_reason] ?? $idea->dismissal_reason }}</div>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Expired</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                @if(($idea->status === 'pending' || $idea->status === 'approved') && $idea->expires_at->isFuture())
                                    {{-- Thumbs up (Approve) --}}
                                    <button wire:click="approve({{ $idea->id }})"
                                            title="Approve - AI will find more like this"
                                            class="p-1.5 rounded-md transition {{ $idea->status === 'approved' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20' }}">
                                        <svg class="w-4 h-4" fill="{{ $idea->status === 'approved' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V3a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m.729-6.307a11.9 11.9 0 0 0-.729 6.307m.729-6.307A48.4 48.4 0 0 1 5.47 4.099a11.74 11.74 0 0 0-3.72.954.75.75 0 0 0-.41.638v6.118c0 .36.193.694.5.864.662.367 1.396.642 2.184.814"/>
                                        </svg>
                                    </button>
                                    {{-- Thumbs down (Dismiss) --}}
                                    <button wire:click="openDismissModal({{ $idea->id }})"
                                            title="Dismiss - AI will avoid similar topics"
                                            class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715A12.137 12.137 0 0 1 2.25 12c0-2.848.992-5.464 2.649-7.521C5.287 3.997 5.886 3.75 6.504 3.75h4.369a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.5a2.25 2.25 0 0 0 2.25 2.25.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398C20.613 14.547 19.833 15 19 15h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54"/>
                                        </svg>
                                    </button>
                                    <div class="w-px h-5 bg-gray-200 dark:bg-gray-600"></div>
                                    <button wire:click="generate({{ $idea->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/>
                                        </svg>
                                        Generate
                                    </button>
                                @elseif($idea->status === 'generated')
                                    <button wire:click="generate({{ $idea->id }})"
                                            title="Regenerate article"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-amber-700 dark:text-amber-400 text-xs font-semibold rounded-md hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/>
                                        </svg>
                                    </button>
                                    @if($idea->generated_post_id)
                                        <a href="{{ route('admin.blog.posts.edit', $idea->generated_post_id) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                            </svg>
                                            Edit Post
                                        </a>
                                    @endif
                                @elseif($idea->status === 'generating')
                                    <span class="text-xs text-gray-400 italic">Working...</span>
                                @elseif($idea->generation_error)
                                    <button wire:click="generate({{ $idea->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-xs font-semibold rounded-md hover:bg-red-100 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-800 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/>
                                        </svg>
                                        Retry
                                    </button>
                                    <button wire:click="dismissGenerationError({{ $idea->id }})"
                                            title="Dismiss error"
                                            class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @elseif($idea->status === 'dismissed')
                                    <button wire:click="restoreIdea({{ $idea->id }})"
                                            class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 text-xs font-semibold">
                                        Restore
                                    </button>
                                @endif
                                <button type="button"
                                        @click="window.tcModal.confirm(@js('Delete idea: '.$idea->title.'?'), {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.delete({{ $idea->id }}))"
                                        title="Delete idea"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
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

    {{-- Poll for generation status when any ideas are generating --}}
    @php $hasGenerating = \App\Models\ContentIdea::where('status', 'generating')->exists(); @endphp
    @if($hasGenerating)
        <div wire:poll.5s></div>
    @endif

    {{-- Idea Detail Modal --}}
    @if($showDetailModal && $detailIdeaId)
        @php $detailIdea = \App\Models\ContentIdea::find($detailIdeaId); @endphp
        @if($detailIdea)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true"
             x-data="{}"
             x-init="document.body.classList.add('overflow-hidden')"
             x-destroy="document.body.classList.remove('overflow-hidden')">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="closeDetailModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full p-6 z-10 max-h-[90vh] overflow-y-auto">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-5">
                        <div class="flex-1 pr-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-snug">{{ $detailIdea->title }}</h3>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $niches[$detailIdea->niche] ?? $detailIdea->niche }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $detailIdea->suggested_post_type === 'listicle' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : ($detailIdea->suggested_post_type === 'quiz' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : ($detailIdea->suggested_post_type === 'trivia' ? 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400')) }}">
                                    {{ ucfirst($detailIdea->suggested_post_type) }}
                                </span>
                                @if($detailIdea->source === 'trends')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Trending</span>
                                @endif
                            </div>
                        </div>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Description --}}
                    <div class="mb-6">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Description</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $detailIdea->description }}</p>
                    </div>

                    {{-- SEO Score Card --}}
                    <div class="mb-6 p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">SEO Potential</h4>

                        <div class="flex items-center gap-4 mb-4">
                            {{-- Score circle --}}
                            <div class="relative flex-shrink-0">
                                <svg class="w-20 h-20 -rotate-90" viewBox="0 0 36 36">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="#e5e7eb" stroke-width="3" class="dark:stroke-gray-700"/>
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke-width="3" stroke-linecap="round"
                                          stroke-dasharray="{{ $detailIdea->seo_score }}, 100"
                                          class="{{ $detailIdea->seo_score >= 70 ? 'stroke-green-500' : ($detailIdea->seo_score >= 50 ? 'stroke-yellow-500' : 'stroke-red-500') }}"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-lg font-bold {{ $detailIdea->seo_score >= 70 ? 'text-green-600 dark:text-green-400' : ($detailIdea->seo_score >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                        {{ $detailIdea->seo_score }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                    @if($detailIdea->seo_score >= 70)
                                        High potential - great topic to pursue
                                    @elseif($detailIdea->seo_score >= 50)
                                        Moderate potential - could perform well
                                    @else
                                        Lower potential - consider alternatives
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Score based on search interest, competition level, Africa relevance, and timeliness.
                                </p>
                            </div>
                        </div>

                        {{-- Score breakdown --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center justify-between px-3 py-2 rounded-md bg-white dark:bg-gray-800">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Search Interest</span>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold
                                    {{ $detailIdea->search_interest === 'high' ? 'text-green-600 dark:text-green-400' : ($detailIdea->search_interest === 'low' ? 'text-red-500' : 'text-yellow-600 dark:text-yellow-400') }}">
                                    @if($detailIdea->search_interest === 'high')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                    @elseif($detailIdea->search_interest === 'low')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                    @endif
                                    {{ ucfirst($detailIdea->search_interest) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2 rounded-md bg-white dark:bg-gray-800">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Competition</span>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold
                                    {{ $detailIdea->competition === 'low' ? 'text-green-600 dark:text-green-400' : ($detailIdea->competition === 'high' ? 'text-red-500' : 'text-yellow-600 dark:text-yellow-400') }}">
                                    @if($detailIdea->competition === 'low')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @elseif($detailIdea->competition === 'high')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                    @endif
                                    {{ ucfirst($detailIdea->competition) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2 rounded-md bg-white dark:bg-gray-800">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Africa Relevance</span>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ $detailIdea->relevance_score }}/10</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2 rounded-md bg-white dark:bg-gray-800">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Source</span>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ ucfirst($detailIdea->source) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Content Details --}}
                    <div class="mb-6 grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Suggested Approach</h4>
                            <div class="space-y-2">
                                @if($detailIdea->suggested_keyword)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Keyword</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">{{ $detailIdea->suggested_keyword }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Tone</span>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($detailIdea->suggested_tone) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Length</span>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($detailIdea->suggested_length) }} (~{{ config('blog.ai.lengths.'.$detailIdea->suggested_length, '1500') }} words)</span>
                                </div>
                                @if($detailIdea->category)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Category</span>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $detailIdea->category }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Timeline</h4>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Researched</span>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $detailIdea->researched_at->format('M j, Y g:i A') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Expires</span>
                                    <span class="text-xs font-medium {{ $detailIdea->expires_at->isPast() ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $detailIdea->expires_at->format('M j, Y') }} ({{ $detailIdea->expires_at->diffForHumans() }})
                                    </span>
                                </div>
                                @if($detailIdea->responded_at)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Reviewed</span>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $detailIdea->responded_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                                @if($detailIdea->source_url)
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Reference</span>
                                        <a href="{{ $detailIdea->source_url }}" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline truncate max-w-[180px]">{{ $detailIdea->source_url }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            {{-- Status badge --}}
                            @if($detailIdea->status === 'approved')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Approved</span>
                            @elseif($detailIdea->status === 'pending' && $detailIdea->expires_at->isFuture())
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Ready</span>
                            @elseif($detailIdea->status === 'generated')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Generated</span>
                            @elseif($detailIdea->status === 'dismissed')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Dismissed</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Expired</span>
                            @endif
                            @if($detailIdea->dismissal_reason)
                                <span class="text-xs text-gray-400">{{ \App\Livewire\Admin\Blog\ContentLab::DISMISSAL_REASONS[$detailIdea->dismissal_reason] ?? $detailIdea->dismissal_reason }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if(($detailIdea->status === 'pending' || $detailIdea->status === 'approved') && $detailIdea->expires_at->isFuture())
                                <button wire:click="approve({{ $detailIdea->id }})" wire:click.self="closeDetailModal"
                                        title="Approve"
                                        class="p-2 rounded-md transition {{ $detailIdea->status === 'approved' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20' }}">
                                    <svg class="w-5 h-5" fill="{{ $detailIdea->status === 'approved' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V3a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m.729-6.307a11.9 11.9 0 0 0-.729 6.307m.729-6.307A48.4 48.4 0 0 1 5.47 4.099a11.74 11.74 0 0 0-3.72.954.75.75 0 0 0-.41.638v6.118c0 .36.193.694.5.864.662.367 1.396.642 2.184.814"/>
                                    </svg>
                                </button>
                                <button wire:click="openDismissModal({{ $detailIdea->id }})"
                                        title="Dismiss"
                                        class="p-2 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715A12.137 12.137 0 0 1 2.25 12c0-2.848.992-5.464 2.649-7.521C5.287 3.997 5.886 3.75 6.504 3.75h4.369a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.5a2.25 2.25 0 0 0 2.25 2.25.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384"/>
                                    </svg>
                                </button>
                                <div class="w-px h-6 bg-gray-200 dark:bg-gray-600"></div>
                                <button wire:click="generate({{ $detailIdea->id }})"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/>
                                    </svg>
                                    Generate Article
                                </button>
                            @elseif($detailIdea->status === 'generated' && $detailIdea->generated_post_id)
                                <button wire:click="generate({{ $detailIdea->id }})"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm font-semibold rounded-md hover:bg-amber-100 dark:hover:bg-amber-900/30 border border-amber-200 dark:border-amber-800 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/>
                                    </svg>
                                    Regenerate
                                </button>
                                <a href="{{ route('admin.blog.posts.edit', $detailIdea->generated_post_id) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                    Edit Post
                                </a>
                            @elseif($detailIdea->status === 'dismissed')
                                <button wire:click="restoreIdea({{ $detailIdea->id }})" wire:click.self="closeDetailModal"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    Restore
                                </button>
                            @endif
                            <button wire:click="closeDetailModal"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif

    {{-- Dismiss Reason Modal --}}
    @if($showDismissModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true"
         x-data="{}"
         x-init="document.body.classList.add('overflow-hidden')"
         x-destroy="document.body.classList.remove('overflow-hidden')">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="closeDismissModal"></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Dismiss Idea</h3>
                    <button wire:click="closeDismissModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ $dismissingIdeaTitle }}</p>

                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Why are you dismissing this idea? <span class="text-gray-400 font-normal">(helps the AI learn)</span></p>

                <div class="space-y-2 mb-6">
                    @foreach(\App\Livewire\Admin\Blog\ContentLab::DISMISSAL_REASONS as $key => $label)
                        <label class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer {{ $dismissalReason === $key ? 'bg-red-50 dark:bg-red-900/10 ring-1 ring-red-200 dark:ring-red-800' : '' }}">
                            <input type="radio"
                                   wire:model="dismissalReason"
                                   value="{{ $key }}"
                                   class="h-4 w-4 border-gray-300 text-red-600 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button wire:click="closeDismissModal"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmDismiss"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715A12.137 12.137 0 0 1 2.25 12c0-2.848.992-5.464 2.649-7.521C5.287 3.997 5.886 3.75 6.504 3.75h4.369a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.5a2.25 2.25 0 0 0 2.25 2.25.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384"/>
                        </svg>
                        Dismiss Idea
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Settings Modal --}}
    @if($showSettingsModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true"
         x-data="{}"
         x-init="document.body.classList.add('overflow-hidden')"
         x-destroy="document.body.classList.remove('overflow-hidden')">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity"
                 @if(! $isSavingSettings) wire:click="closeSettings" @endif></div>

            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full z-10">

                {{-- Research Progress Overlay --}}
                <div wire:loading.flex wire:target="saveSettings"
                     class="absolute inset-0 z-20 items-center justify-center rounded-xl bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm">
                    <div class="text-center px-8 py-12 w-full">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 mb-5">
                            <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Researching Content Ideas</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            Researching {{ count(array_filter($nicheToggles)) }} niche(s) via Perplexity AI.
                            This may take 1-3 minutes...
                        </p>

                        {{-- Animated progress bar --}}
                        <div class="w-full max-w-xs mx-auto">
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-600 rounded-full animate-progress-indeterminate"></div>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Settings saved. Fetching fresh ideas...</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Content Lab Settings</h3>
                        <button wire:click="closeSettings"
                                wire:loading.attr="disabled" wire:target="saveSettings"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-30 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Flash messages inside modal --}}
                    @if(session('settings-error'))
                        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
                            {{ session('settings-error') }}
                        </div>
                    @endif
                    @if(session('settings-success'))
                        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
                            {{ session('settings-success') }}
                        </div>
                    @endif

                    {{-- Niches Section --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Research Niches</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Toggle which niches the AI agent researches content ideas for.</p>

                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @foreach($nicheToggles as $key => $enabled)
                                <label class="flex items-center justify-between gap-3 px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $niches[$key] ?? $key }}</span>
                                        @if(! in_array($key, $defaultNicheKeys))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">Custom</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(! in_array($key, $defaultNicheKeys))
                                            <button type="button"
                                                    wire:click="removeCustomNiche('{{ $key }}')"
                                                    class="text-red-400 hover:text-red-600 dark:hover:text-red-300"
                                                    title="Remove custom niche">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <input type="checkbox"
                                               wire:model="nicheToggles.{{ $key }}"
                                               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Add Custom Niche --}}
                    <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Add Custom Niche</h4>
                        <div class="flex gap-2">
                            <input wire:model="newNicheLabel" type="text" placeholder="e.g. Fashion, Lifestyle, Education"
                                   class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   wire:keydown.enter="addCustomNiche">
                            <button type="button"
                                    wire:click="addCustomNiche"
                                    class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Separate multiple niches with commas. Slugs are auto-generated.</p>
                    </div>

                    {{-- Research Context --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Research Context</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Provide instructions or feedback for the AI agent to fine-tune the type of content ideas it researches. This context is included in every research prompt.</p>
                        <textarea wire:model="researchContext"
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="e.g. Focus on emerging tech startups in West Africa. Avoid content about political conflicts. Prioritize articles that highlight positive developments and innovation across the continent..."
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ strlen($researchContext) }} / 1000 characters</p>
                    </div>

                    {{-- Research after save --}}
                    <div class="mb-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   wire:model="researchAfterSave"
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                            <div>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Run research now after saving</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Clean up expired ideas and fetch fresh content ideas using the updated settings. This may take 1-3 minutes.</p>
                            </div>
                        </label>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3">
                        <button wire:click="closeSettings"
                                wire:loading.attr="disabled" wire:target="saveSettings"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed transition">
                            Cancel
                        </button>
                        <button wire:click="saveSettings"
                                wire:loading.attr="disabled"
                                wire:target="saveSettings"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-60 transition">
                            <svg wire:loading wire:target="saveSettings" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                            <span wire:loading wire:target="saveSettings">Saving & Researching...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes progress-indeterminate {
            0% { transform: translateX(-100%); width: 40%; }
            50% { transform: translateX(60%); width: 60%; }
            100% { transform: translateX(200%); width: 40%; }
        }
        .animate-progress-indeterminate {
            animation: progress-indeterminate 1.8s ease-in-out infinite;
        }
    </style>
    @endif
</div>
