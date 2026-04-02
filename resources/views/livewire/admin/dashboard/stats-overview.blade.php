<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        {{-- Total Posts --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-indigo-50 dark:bg-indigo-900/30 p-3">
                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Posts</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->postCounts['total']) }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-sm">
                <span class="text-green-600 dark:text-green-400">{{ $this->postCounts['published'] }} published</span>
                <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                <span class="text-yellow-600 dark:text-yellow-400">{{ $this->postCounts['draft'] }} drafts</span>
            </div>
        </div>

        {{-- Total Views --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-green-50 dark:bg-green-900/30 p-3">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Views</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->viewStats['total']) }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-sm">
                <span class="text-blue-600 dark:text-blue-400">{{ number_format($this->viewStats['today']) }} today</span>
                <span class="text-gray-300 dark:text-gray-600">&middot;</span>
                <span class="text-gray-600 dark:text-gray-400">{{ number_format($this->viewStats['week']) }} this week</span>
            </div>
        </div>

        {{-- Scheduled Posts --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-blue-50 dark:bg-blue-900/30 p-3">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->postCounts['scheduled']) }}</p>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Posts awaiting publication
            </div>
        </div>

        {{-- Monthly Views --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md bg-purple-50 dark:bg-purple-900/30 p-3">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Views</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->viewStats['month']) }}</p>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Last 30 days
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Views Chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Views Overview</h3>
                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    @foreach (['today' => 'Today', 'week' => 'Week', 'month' => 'Month'] as $period => $label)
                        <button
                            wire:click="setViewsPeriod('{{ $period }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                                {{ $viewsPeriod === $period
                                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Simple Bar Chart --}}
            <div class="flex items-end gap-1 h-48" wire:loading.class="opacity-50">
                @php
                    $chartData = $this->viewsChartData;
                    $maxViews = max(array_column($chartData, 'views')) ?: 1;
                @endphp
                @foreach ($chartData as $point)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $point['views'] }}</span>
                        <div
                            class="w-full bg-indigo-500 dark:bg-indigo-400 rounded-t transition-all duration-300"
                            style="height: {{ max(4, ($point['views'] / $maxViews) * 100) }}%"
                        ></div>
                        <span class="text-xs text-gray-400 dark:text-gray-500 truncate w-full text-center">{{ $point['date'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Popular Posts --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Popular Posts</h3>
            <div class="space-y-4">
                @forelse ($this->popularPosts as $index => $post)
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate block">
                                {{ $post->title }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ number_format($post->views_count) }} views
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No published posts yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Posts Table --}}
    <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Posts</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->recentPosts as $post)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ Str::limit($post->title, 50) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->author?->name ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'draft' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'scheduled' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$post->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ ucfirst($post->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No posts yet. Create your first post to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Comments --}}
    <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Comments</h3>
        <div class="space-y-4">
            @forelse ($this->recentComments as $comment)
                <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
                            {{ strtoupper(substr($comment->user?->name ?? 'A', 0, 1)) }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->user?->name ?? 'Anonymous' }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ Str::limit($comment->body ?? $comment->content ?? '', 120) }}</p>
                        @if ($comment->post)
                            <a href="{{ route('admin.blog.posts.edit', $comment->post->id) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1 inline-block">
                                on: {{ Str::limit($comment->post->title, 40) }}
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No comments yet.</p>
            @endforelse
        </div>
    </div>
</div>
