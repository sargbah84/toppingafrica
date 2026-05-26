<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Posts</h1>
        <div class="flex items-center gap-3">
            <button type="button"
               x-on:click="$dispatch('open-ai-generator')"
               class="inline-flex items-center gap-2 px-4 py-2 border border-indigo-300 dark:border-indigo-700 rounded-lg text-sm font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                </svg>
                AI Generate
            </button>
            <a href="{{ route('admin.blog.posts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Post
            </a>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px overflow-x-auto px-4" aria-label="Tabs">
                @foreach (['all' => 'All', 'published' => 'Published', 'draft' => 'Drafts', 'scheduled' => 'Scheduled', 'trashed' => 'Trashed'] as $status => $label)
                    <button
                        wire:click="filterByStatus('{{ $status }}')"
                        class="flex-shrink-0 px-4 py-3 text-sm font-medium border-b-2 transition-colors
                            {{ $statusFilter === $status
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}"
                    >
                        {{ $label }}
                        <span class="ml-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs
                            {{ $statusFilter === $status
                                ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $this->statusCounts[$status] ?? 0 }}
                        </span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Search & Per Page --}}
        <div class="p-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search posts..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                />
                <div wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>
            <select wire:model.live="perPage" class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm py-2 px-3">
                <option value="15">15 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
            </select>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    @if (count($selectedPosts) > 0)
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 mb-4 flex flex-col sm:flex-row items-center gap-4">
            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                {{ count($selectedPosts) }} post(s) selected
            </span>
            <div class="flex items-center gap-2">
                <select wire:model="bulkAction" class="border border-indigo-300 dark:border-indigo-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm py-1.5 px-3">
                    <option value="">Choose action...</option>
                    <option value="publish">Publish</option>
                    <option value="unpublish">Unpublish</option>
                    <option value="delete">Move to Trash</option>
                </select>
                <button
                    wire:click="applyBulkAction"
                    wire:loading.attr="disabled"
                    class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="applyBulkAction">Apply</span>
                    <span wire:loading wire:target="applyBulkAction">Applying...</span>
                </button>
            </div>
            <button wire:click="$set('selectedPosts', [])" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                Clear selection
            </button>
        </div>
    @endif

    {{-- Posts Table --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="w-12 px-4 py-3">
                            <input
                                type="checkbox"
                                wire:model.live="selectAll"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                            />
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sortBy('title')" class="flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300">
                                Title
                                @if ($sortField === 'title')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Categories</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SEO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creators</th>
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sortBy('views_count')" class="flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300">
                                Views
                                @if ($sortField === 'views_count')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sortBy('reactions_count')" class="flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300">
                                Reactions
                                @if ($sortField === 'reactions_count')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sortBy('created_at')" class="flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-300">
                                Date
                                @if ($sortField === 'created_at')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="w-20 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700" wire:loading.class="opacity-50">
                    @forelse ($this->posts as $post)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedPosts"
                                    value="{{ $post->id }}"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ Str::limit($post->title, 60) }}
                                </a>
                                @if ($post->is_featured)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                        Featured
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->author?->name ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($post->categories as $category)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'draft' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'scheduled' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$post->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ ucfirst($post->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $seoScore = $post->latestSeoAnalysis?->overall_score;
                                    if ($seoScore === null) {
                                        $seoColor = 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
                                    } elseif ($seoScore >= 80) {
                                        $seoColor = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                                    } elseif ($seoScore >= 60) {
                                        $seoColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
                                    } else {
                                        $seoColor = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $seoColor }}">
                                    {{ $seoScore !== null ? $seoScore : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                @if (($post->creators_count ?? 0) > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                        {{ $post->creators_count }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($post->views_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($post->reactions_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $post->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right" x-data="{ open: false }">
                                <div class="relative">
                                    <button @click="open = !open" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        @click.away="open = false"
                                        x-transition
                                        class="absolute right-0 z-10 mt-1 w-44 bg-white dark:bg-gray-700 shadow-lg rounded-lg border border-gray-200 dark:border-gray-600 py-1"
                                    >
                                        <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            Edit
                                        </a>
                                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            Preview
                                        </a>
                                        @if ($statusFilter === 'trashed')
                                            <button wire:click="restorePost({{ $post->id }})" @click="open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                Restore
                                            </button>
                                            <button type="button" @click="open = false; window.tcModal.confirm('Are you sure you want to permanently delete this post?', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.forceDeletePost({{ $post->id }}))" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                Delete Permanently
                                            </button>
                                        @else
                                            <button type="button" @click="open = false; window.tcModal.confirm('Are you sure you want to move this post to trash?', {variant:'warning', confirmText:'Move to trash'}).then(ok => ok && $wire.deletePost({{ $post->id }}))" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                Move to Trash
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9.75m3 0H9.75m0 0v-3.375" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No posts found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->posts->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->posts->links() }}
            </div>
        @endif
    </div>

    {{-- AI Post Generator Modal --}}
    <livewire:admin.blog.post-generator />
</div>
