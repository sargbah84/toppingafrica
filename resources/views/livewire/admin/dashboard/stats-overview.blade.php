<div>
    {{-- Time Period Filters --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-2">
            @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'all' => 'All Time'] as $period => $label)
                <button
                    wire:click="setTimePeriod('{{ $period }}')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                        {{ $timePeriod === $period
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5">
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <input type="date" wire:model.live="dateFrom" class="text-sm bg-transparent border-none focus:ring-0 text-gray-600 dark:text-gray-400 p-0 w-32">
                <span class="text-gray-400">-</span>
                <input type="date" wire:model.live="dateTo" class="text-sm bg-transparent border-none focus:ring-0 text-gray-600 dark:text-gray-400 p-0 w-32">
                <button wire:click="setDateRange" class="text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:underline">Go</button>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        {{-- Total Posts --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Posts</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->totalPosts) }}</p>
                </div>
                <div class="flex-shrink-0 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 p-3">
                    <svg class="h-7 w-7 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Users --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->totalUsers) }}</p>
                </div>
                <div class="flex-shrink-0 rounded-xl bg-blue-50 dark:bg-blue-900/30 p-3">
                    <svg class="h-7 w-7 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Page Views --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Page Views</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->pageViews) }}</p>
                </div>
                <div class="flex-shrink-0 rounded-xl bg-green-50 dark:bg-green-900/30 p-3">
                    <svg class="h-7 w-7 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Subscribers --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subscribers</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->totalSubscribers) }}</p>
                </div>
                <div class="flex-shrink-0 rounded-xl bg-purple-50 dark:bg-purple-900/30 p-3">
                    <svg class="h-7 w-7 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex items-center gap-1 mb-6 bg-white dark:bg-gray-800 rounded-xl shadow p-1.5">
        @foreach (['overview' => ['Overview', 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5'], 'traffic' => ['Traffic', 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'], 'search' => ['Search', 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'], 'tools' => ['Tools', 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z'], 'content' => ['Content', 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5'], 'growth' => ['Growth', 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'], 'engagement' => ['Engagement', 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z']] as $tab => [$label, $icon])
            <button
                wire:click="setTab('{{ $tab }}')"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors
                    {{ $activeTab === $tab
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.delay class="fixed inset-0 bg-black/10 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg px-6 py-4 flex items-center gap-3">
            <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm text-gray-600 dark:text-gray-400">Loading analytics...</span>
        </div>
    </div>

    {{-- ═══ OVERVIEW TAB ═══ --}}
    @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Views Growth Chart --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Page Views (Last 30 Days)</h3>
                @php $chartData = $this->viewsGrowthChart; @endphp
                <div
                    wire:key="growth-chart"
                    x-data="viewsChart({{ Js::from($chartData) }})"
                    x-init="draw(); window.addEventListener('resize', () => draw())"
                    class="relative"
                >
                    <canvas x-ref="canvas" class="w-full" style="height: 240px;"></canvas>
                    <div x-show="tooltip.show" x-cloak
                         class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-3 py-1.5 shadow-lg whitespace-nowrap"
                         :style="'left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%);'"
                         x-text="tooltip.label + ': ' + tooltip.value + ' views'">
                    </div>
                </div>
            </div>

            {{-- Post Status Breakdown --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Content Status</h3>
                <div class="space-y-4">
                    @php
                        $statuses = $this->postsByStatus;
                        $total = max(array_sum($statuses), 1);
                        $statusConfig = [
                            'published' => ['label' => 'Published', 'color' => 'bg-green-500'],
                            'draft' => ['label' => 'Drafts', 'color' => 'bg-yellow-500'],
                            'scheduled' => ['label' => 'Scheduled', 'color' => 'bg-blue-500'],
                        ];
                    @endphp
                    @foreach ($statusConfig as $status => $config)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $config['label'] }}</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $statuses[$status] }}
                                    <span class="text-xs text-gray-500 font-normal">({{ round(($statuses[$status] / $total) * 100, 1) }}%)</span>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $config['color'] }} h-2 rounded-full transition-all duration-500" style="width: {{ ($statuses[$status] / $total) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Bottom Row: Top Pages, Top Referrers, Devices --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mt-6">
            {{-- Top Pages --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Top Pages</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($this->topPages as $page)
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1" title="/{{ $page->slug }}">
                                /{{ Str::limit($page->slug, 25) }}
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0">{{ number_format($page->view_count) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No page views recorded yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Referrers --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Top Referrers</h3>
                <div class="space-y-3">
                    @forelse ($this->topReferrers as $referrer)
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <div class="w-1.5 h-5 rounded-full bg-indigo-500 flex-shrink-0"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $referrer->domain }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0">{{ number_format($referrer->count) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No referrer data yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Devices --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Devices</h3>
                <div class="space-y-5">
                    @php
                        $deviceIcons = [
                            'Desktop' => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25h-13.5A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25h-13.5A2.25 2.25 0 0 1 3 12V5.25',
                            'Mobile' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
                            'Tablet' => 'M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-15a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25Z',
                        ];
                        $deviceColors = [
                            'Desktop' => 'text-indigo-600 dark:text-indigo-400',
                            'Mobile' => 'text-green-600 dark:text-green-400',
                            'Tablet' => 'text-orange-600 dark:text-orange-400',
                        ];
                        $barColors = [
                            'Desktop' => 'bg-indigo-500',
                            'Mobile' => 'bg-green-500',
                            'Tablet' => 'bg-orange-500',
                        ];
                    @endphp
                    @forelse ($this->deviceBreakdown as $device)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 {{ $deviceColors[$device['type']] ?? 'text-gray-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $deviceIcons[$device['type']] ?? '' }}" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $device['type'] }}</span>
                                </div>
                                <span class="text-sm font-bold {{ $deviceColors[$device['type']] ?? 'text-gray-900 dark:text-white' }}">{{ $device['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $barColors[$device['type']] ?? 'bg-gray-500' }} h-2 rounded-full transition-all duration-500" style="width: {{ $device['percentage'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($device['count']) }} views</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No device data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    {{-- ═══ TRAFFIC TAB ═══ --}}
    @elseif ($activeTab === 'traffic')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 mb-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Views</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->pageViews) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unique Visitors</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->uniqueVisitors) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Views/Day</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->avgViewsPerDay }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bounce Rate</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">--</p>
                <p class="text-xs text-gray-400 mt-1">Coming soon</p>
            </div>
        </div>

        {{-- Traffic Chart --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Traffic Overview</h3>
            @php $trafficData = $this->trafficByDay; @endphp
            <div
                wire:key="traffic-chart-{{ $timePeriod }}"
                x-data="trafficChart({{ Js::from($trafficData) }})"
                x-init="draw(); window.addEventListener('resize', () => draw())"
                class="relative"
            >
                <canvas x-ref="canvas" class="w-full" style="height: 280px;"></canvas>
                <div x-show="tooltip.show" x-cloak
                     class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-lg whitespace-nowrap"
                     :style="'left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%);'"
                     x-html="tooltip.label">
                </div>
            </div>
            <div class="flex items-center gap-6 mt-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Views</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Unique Visitors</span>
                </div>
            </div>
        </div>

        {{-- Referrers & Devices Side by Side --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Top Referrers</h3>
                <div class="space-y-3">
                    @forelse ($this->topReferrers as $referrer)
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <div class="w-1.5 h-5 rounded-full bg-indigo-500 flex-shrink-0"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $referrer->domain }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0">{{ number_format($referrer->count) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No referrer data yet.</p>
                    @endforelse
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Devices</h3>
                <div class="space-y-4">
                    @forelse ($this->deviceBreakdown as $device)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ $deviceColors[$device['type']] ?? 'text-gray-500' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $deviceIcons[$device['type']] ?? '' }}" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $device['type'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $device['percentage'] }}%</span>
                                <span class="text-xs text-gray-500 ml-1">({{ number_format($device['count']) }})</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No device data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    {{-- ═══ CONTENT TAB ═══ --}}
    @elseif ($activeTab === 'content')
        {{-- Content Stats --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
            {{-- Posts by Type --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Posts by Type</h3>
                <div class="space-y-3">
                    @php
                        $typeColors = [
                            'article' => 'bg-indigo-500',
                            'video' => 'bg-red-500',
                            'quiz' => 'bg-yellow-500',
                            'trivia' => 'bg-pink-500',
                            'listicle' => 'bg-cyan-500',
                            'gallery' => 'bg-orange-500',
                            'poll' => 'bg-purple-500',
                        ];
                        $typeTotal = max($this->postsByType->sum('count'), 1);
                    @endphp
                    @forelse ($this->postsByType as $type)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $type->post_type ?? 'article' }}</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $type->count }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $typeColors[$type->post_type] ?? 'bg-gray-500' }} h-2 rounded-full" style="width: {{ ($type->count / $typeTotal) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No posts yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Top Categories --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Top Categories</h3>
                <div class="space-y-3">
                    @forelse ($this->topCategories as $category)
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->post_count }} posts</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No categories yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Posts Table --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Recent Posts</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Views</th>
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$post->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($post->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($post->views_count) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $post->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No posts yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SEO Intelligence Section --}}
        @php $seo = $this->seoOverview; $scores = $this->seoScoreDistribution; @endphp
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mt-6">
            {{-- SEO Score Summary --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">SEO Intelligence</h3>
                <div class="text-center mb-4">
                    <p class="text-4xl font-bold {{ $seo['avg_score'] >= 70 ? 'text-green-600 dark:text-green-400' : ($seo['avg_score'] >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $seo['avg_score'] }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg SEO Score ({{ $seo['total'] }} analyzed)</p>
                </div>
                @php
                    $seoCategories = [
                        'content' => ['Content', 'bg-indigo-500'],
                        'technical' => ['Technical', 'bg-blue-500'],
                        'readability' => ['Readability', 'bg-green-500'],
                        'engagement' => ['Engagement', 'bg-purple-500'],
                        'on_page' => ['On-Page', 'bg-orange-500'],
                    ];
                @endphp
                <div class="space-y-2">
                    @foreach ($seoCategories as $key => [$label, $color])
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $label }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="{{ $color }} h-1.5 rounded-full" style="width: {{ $scores[$key] }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-8 text-right">{{ $scores[$key] }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Low Score Posts --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Needs SEO Improvement</h3>
                <div class="space-y-2">
                    @forelse ($this->lowSeoScorePosts as $post)
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-indigo-600 truncate flex-1">
                                {{ Str::limit($post->title, 30) }}
                            </a>
                            <span class="text-xs font-bold px-1.5 py-0.5 rounded {{ ($post->seo_score ?? 0) >= 50 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $post->seo_score ?? 0 }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">All posts have good scores.</p>
                    @endforelse
                </div>
            </div>

            {{-- Missing Analysis --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Missing SEO Analysis</h3>
                <div class="space-y-2">
                    @forelse ($this->postsNeedingSeo->take(8) as $post)
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-indigo-600 truncate flex-1">
                                {{ Str::limit($post->title, 35) }}
                            </a>
                            <span class="text-xs text-gray-400">{{ $post->published_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">All posts analyzed.</p>
                    @endforelse
                </div>
            </div>
        </div>

    {{-- ═══ SEARCH (GSC) TAB ═══ --}}
    @elseif ($activeTab === 'search')
        @if (!$this->gscIsConfigured)
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-8 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Connect Google Search Console</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    Monitor your search positions, clicks, impressions, and top queries by connecting your Google Search Console account.
                </p>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 max-w-lg mx-auto text-left">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Setup Instructions:</p>
                    <ol class="text-sm text-gray-600 dark:text-gray-400 space-y-2 list-decimal list-inside">
                        <li>Create a Google Cloud project and enable the Search Console API</li>
                        <li>Create OAuth 2.0 credentials (Web application type)</li>
                        <li>Add these environment variables to your <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">.env</code>:</li>
                    </ol>
                    <pre class="mt-3 text-xs bg-gray-900 text-green-400 p-3 rounded-lg overflow-x-auto">GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN=your-refresh-token
GOOGLE_SEARCH_CONSOLE_SITE_URL=https://toppingafrica.com</pre>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                        Then run <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">php artisan gsc:sync</code> to pull data, or schedule it daily.
                    </p>
                </div>
            </div>
        @else
            @php $gsc = $this->gscTotals; @endphp

            {{-- GSC Summary Cards --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Clicks</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($gsc['clicks']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Impressions</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($gsc['impressions']) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Position</p>
                    <p class="text-2xl font-bold {{ $gsc['avg_position'] <= 10 ? 'text-green-600 dark:text-green-400' : ($gsc['avg_position'] <= 30 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }} mt-1">
                        {{ $gsc['avg_position'] }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg CTR</p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $gsc['avg_ctr'] }}%</p>
                        </div>
                        <button wire:click="syncSearchConsole" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            Sync Now
                        </button>
                    </div>
                </div>
            </div>

            {{-- Search Performance Chart --}}
            @php $searchTrend = $this->gscTrend; @endphp
            @if (count($searchTrend) > 0)
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Search Performance</h3>
                    <div
                        wire:key="gsc-chart"
                        x-data="gscChart({{ Js::from($searchTrend) }})"
                        x-init="draw(); window.addEventListener('resize', () => draw())"
                        class="relative"
                    >
                        <canvas x-ref="canvas" class="w-full" style="height: 280px;"></canvas>
                        <div x-show="tooltip.show" x-cloak
                             class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-lg whitespace-nowrap"
                             :style="'left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%);'"
                             x-html="tooltip.label">
                        </div>
                    </div>
                    <div class="flex items-center gap-6 mt-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Clicks</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Impressions</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Top Queries & Top Pages --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Top Search Queries</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                                    <th class="pb-2">Query</th>
                                    <th class="pb-2 text-right">Clicks</th>
                                    <th class="pb-2 text-right">Impr.</th>
                                    <th class="pb-2 text-right">Pos.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($this->gscTopQueries as $q)
                                    <tr>
                                        <td class="py-2 text-gray-700 dark:text-gray-300 truncate max-w-[200px]">{{ $q->query }}</td>
                                        <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($q->total_clicks) }}</td>
                                        <td class="py-2 text-right text-gray-500">{{ number_format($q->total_impressions) }}</td>
                                        <td class="py-2 text-right">
                                            <span class="{{ round($q->avg_position) <= 10 ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                                                {{ round($q->avg_position, 1) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-4 text-center text-gray-500">No search data yet. Run <code>php artisan gsc:sync</code>.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Top Pages in Search</h3>
                    <div class="space-y-3">
                        @forelse ($this->gscTopPages as $page)
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1" title="{{ $page->page }}">
                                    {{ Str::limit(str_replace(config('app.url'), '', $page->page), 40) }}
                                </span>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($page->total_clicks) }} clicks</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded {{ round($page->avg_position) <= 10 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                        #{{ round($page->avg_position) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No page data yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

    {{-- ═══ TOOLS (AI USAGE) TAB ═══ --}}
    @elseif ($activeTab === 'tools')
        {{-- AI Usage Summary Cards --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Requests</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->aiTotalRequests) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Tokens</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($this->aiTotalTokens) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estimated Cost</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">${{ number_format($this->aiTotalCost, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Success Rate</p>
                <p class="text-2xl font-bold {{ $this->aiSuccessRate >= 95 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }} mt-1">{{ $this->aiSuccessRate }}%</p>
            </div>
        </div>

        {{-- Usage Trend Chart --}}
        @php $aiTrend = $this->aiUsageTrend; @endphp
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">AI Usage (Last 30 Days)</h3>
            <div
                wire:key="ai-trend-chart"
                x-data="aiUsageChart({{ Js::from($aiTrend) }})"
                x-init="draw(); window.addEventListener('resize', () => draw())"
                class="relative"
            >
                <canvas x-ref="canvas" class="w-full" style="height: 240px;"></canvas>
                <div x-show="tooltip.show" x-cloak
                     class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-lg whitespace-nowrap"
                     :style="'left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%);'"
                     x-html="tooltip.label">
                </div>
            </div>
            <div class="flex items-center gap-6 mt-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Requests</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Cost ($)</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
            {{-- Usage by Provider --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Usage by Provider</h3>
                @php
                    $providerColors = [
                        'openai' => ['bg-green-500', 'text-green-600 dark:text-green-400'],
                        'perplexity' => ['bg-blue-500', 'text-blue-600 dark:text-blue-400'],
                        'anthropic' => ['bg-orange-500', 'text-orange-600 dark:text-orange-400'],
                    ];
                    $providerTotal = max($this->aiUsageByProvider->sum('requests'), 1);
                @endphp
                <div class="space-y-4">
                    @forelse ($this->aiUsageByProvider as $provider)
                        @php $pColor = $providerColors[$provider->provider] ?? ['bg-gray-500', 'text-gray-600']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $provider->provider }}</span>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($provider->requests) }} req</span>
                                    <span class="text-xs text-gray-500 ml-1">${{ number_format((float)$provider->total_cost, 2) }}</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $pColor[0] }} h-2 rounded-full" style="width: {{ ($provider->requests / $providerTotal) * 100 }}%"></div>
                            </div>
                            <div class="flex items-center justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ number_format($provider->total_tokens) }} tokens</span>
                                <span>~{{ number_format((float)$provider->avg_duration) }}ms avg</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No AI usage recorded yet. Generate content to see stats.</p>
                    @endforelse
                </div>
            </div>

            {{-- Usage by Feature --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Usage by Feature</h3>
                @php
                    $featureColors = [
                        'blog-generation' => 'bg-indigo-500',
                        'blog-social-sharing' => 'bg-pink-500',
                        'title-suggestion' => 'bg-cyan-500',
                        'seo-analysis' => 'bg-yellow-500',
                    ];
                    $featureTotal = max($this->aiUsageByFeature->sum('requests'), 1);
                @endphp
                <div class="space-y-4">
                    @forelse ($this->aiUsageByFeature as $feature)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ str_replace('-', ' ', ucfirst($feature->feature)) }}
                                </span>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($feature->requests) }}</span>
                                    <span class="text-xs text-gray-500 ml-1">${{ number_format((float)$feature->total_cost, 2) }}</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $featureColors[$feature->feature] ?? 'bg-gray-500' }} h-2 rounded-full" style="width: {{ ($feature->requests / $featureTotal) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No AI usage recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent AI Logs --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Recent AI Requests</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Provider</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Feature</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tokens</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Duration</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->aiRecentLogs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 capitalize">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $log->provider }}</span>
                                    <span class="text-xs text-gray-500 block">{{ $log->model }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ str_replace('-', ' ', ucfirst($log->feature)) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                    {{ number_format($log->total_tokens) }}
                                    <span class="text-xs text-gray-400 block">{{ number_format($log->input_tokens) }}in / {{ number_format($log->output_tokens) }}out</span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">${{ number_format((float)$log->estimated_cost, 4) }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $log->duration_ms ? number_format($log->duration_ms) . 'ms' : '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($log->is_successful)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">OK</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Fail</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No AI requests logged yet. Generate content to start tracking.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- ═══ GROWTH TAB ═══ --}}
    @elseif ($activeTab === 'growth')
        @php $growth = $this->growthStats; @endphp

        {{-- Growth Comparison Cards --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            @php
                $growthItems = [
                    ['label' => 'Users', 'data' => $growth['users'], 'color' => 'blue'],
                    ['label' => 'Subscribers', 'data' => $growth['subscribers'], 'color' => 'purple'],
                    ['label' => 'Posts', 'data' => $growth['posts'], 'color' => 'indigo'],
                    ['label' => 'Views', 'data' => $growth['views'], 'color' => 'green'],
                ];
            @endphp
            @foreach ($growthItems as $item)
                @php
                    $change = $item['data']['previous'] > 0
                        ? round((($item['data']['current'] - $item['data']['previous']) / $item['data']['previous']) * 100, 1)
                        : ($item['data']['current'] > 0 ? 100 : 0);
                    $isUp = $change >= 0;
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $item['label'] }} This Month</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($item['data']['current']) }}</p>
                    <div class="flex items-center gap-1 mt-2">
                        @if ($isUp)
                            <svg class="w-4 h-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $change }}%</span>
                        @else
                            <svg class="w-4 h-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" />
                            </svg>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $change }}%</span>
                        @endif
                        <span class="text-xs text-gray-400">vs last month ({{ number_format($item['data']['previous']) }})</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Growth Charts --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- User Growth --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">User Growth (Last 30 Days)</h3>
                @php $userData = $this->userGrowth; @endphp
                <div
                    wire:key="user-growth-chart"
                    x-data="viewsChart({{ Js::from($userData) }})"
                    x-init="draw(); window.addEventListener('resize', () => draw())"
                    class="relative"
                >
                    <canvas x-ref="canvas" class="w-full" style="height: 200px;"></canvas>
                    <div x-show="tooltip.show" x-cloak
                         class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-3 py-1.5 shadow-lg whitespace-nowrap"
                         :style="'left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%);'"
                         x-text="tooltip.label + ': ' + tooltip.value + ' users'">
                    </div>
                </div>
            </div>

            {{-- Subscriber Growth --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Subscriber Growth (Last 30 Days)</h3>
                @php $subData = $this->subscriberGrowth; @endphp
                <div
                    wire:key="sub-growth-chart"
                    x-data="growthLineChart({{ Js::from($subData) }})"
                    x-init="draw(); window.addEventListener('resize', () => draw())"
                    class="relative"
                >
                    <canvas x-ref="canvas" class="w-full" style="height: 200px;"></canvas>
                    <div x-show="tooltip.show" x-cloak
                         class="absolute pointer-events-none bg-gray-900 text-white text-xs rounded-lg px-3 py-1.5 shadow-lg whitespace-nowrap"
                         :style="'left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%);'"
                         x-text="tooltip.label + ': ' + tooltip.value + ' subscribers'">
                    </div>
                </div>
            </div>
        </div>

    {{-- ═══ ENGAGEMENT TAB ═══ --}}
    @elseif ($activeTab === 'engagement')
        {{-- Comment Stats --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-6">
            @php $cStats = $this->commentStats; @endphp
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Comments</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($cStats['total']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Approved</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($cStats['approved']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending Review</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ number_format($cStats['pending']) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Popular Posts --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Most Viewed Posts</h3>
                <div class="space-y-3">
                    @forelse ($this->popularPosts as $index => $post)
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-bold text-gray-600 dark:text-gray-400">
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

            {{-- Recent Comments --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Recent Comments</h3>
                <div class="space-y-3">
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
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ Str::limit($comment->body ?? $comment->content ?? '', 100) }}</p>
                                @if ($comment->post)
                                    <a href="{{ route('admin.blog.posts.edit', $comment->post->id) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1 inline-block">
                                        on: {{ Str::limit($comment->post->title, 35) }}
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
    @endif

    {{-- Charts JavaScript --}}
    <script>
    function viewsChart(data) {
        return {
            tooltip: { show: false, x: 0, y: 0, label: '', value: 0 },
            draw() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                const w = rect.width, h = rect.height;
                const pad = { top: 20, right: 16, bottom: 32, left: 44 };
                const cw = w - pad.left - pad.right;
                const ch = h - pad.top - pad.bottom;

                ctx.clearRect(0, 0, w, h);

                const values = data.map(d => d.views);
                const labels = data.map(d => d.date);
                const rawMax = Math.max(...values);

                let niceMax, stepVal;
                const steps = 5;
                if (rawMax <= 0) { niceMax = 100; stepVal = 20; }
                else if (rawMax <= 5) { niceMax = 5; stepVal = 1; }
                else if (rawMax <= 10) { niceMax = 10; stepVal = 2; }
                else {
                    const magnitude = Math.pow(10, Math.floor(Math.log10(rawMax)));
                    const normalized = rawMax / magnitude;
                    if (normalized <= 1.5) niceMax = 1.5 * magnitude;
                    else if (normalized <= 2) niceMax = 2 * magnitude;
                    else if (normalized <= 3) niceMax = 3 * magnitude;
                    else if (normalized <= 5) niceMax = 5 * magnitude;
                    else if (normalized <= 7.5) niceMax = 7.5 * magnitude;
                    else niceMax = 10 * magnitude;
                    stepVal = niceMax / steps;
                }

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                const textColor = isDark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.4)';
                const lineColor = '#6366f1';

                ctx.font = '11px system-ui, sans-serif';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                for (let i = 0; i <= steps; i++) {
                    const y = pad.top + ch - (i / steps) * ch;
                    ctx.strokeStyle = gridColor;
                    ctx.lineWidth = 1;
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.moveTo(pad.left, y);
                    ctx.lineTo(w - pad.right, y);
                    ctx.stroke();
                    ctx.fillStyle = textColor;
                    ctx.fillText(Math.round(i * stepVal).toLocaleString(), pad.left - 8, y);
                }

                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';
                const points = [];
                const skipInterval = data.length > 14 ? Math.ceil(data.length / 7) : (data.length > 7 ? 2 : 1);
                for (let i = 0; i < data.length; i++) {
                    const x = pad.left + (i / Math.max(data.length - 1, 1)) * cw;
                    const y = pad.top + ch - (values[i] / niceMax) * ch;
                    points.push({ x, y });
                    if (i % skipInterval === 0 || i === data.length - 1) {
                        ctx.fillStyle = textColor;
                        ctx.fillText(labels[i], x, pad.top + ch + 10);
                    }
                }

                if (points.length === 0) return;

                function drawSmoothLine(pts) {
                    if (pts.length < 2) return;
                    ctx.moveTo(pts[0].x, pts[0].y);
                    if (pts.length === 2) { ctx.lineTo(pts[1].x, pts[1].y); return; }
                    for (let i = 0; i < pts.length - 1; i++) {
                        const cpx = (pts[i].x + pts[i + 1].x) / 2;
                        ctx.quadraticCurveTo(pts[i].x, pts[i].y, cpx, (pts[i].y + pts[i + 1].y) / 2);
                    }
                    ctx.lineTo(pts[pts.length - 1].x, pts[pts.length - 1].y);
                }

                const gradient = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
                gradient.addColorStop(0, isDark ? 'rgba(99,102,241,0.25)' : 'rgba(99,102,241,0.15)');
                gradient.addColorStop(1, isDark ? 'rgba(99,102,241,0.02)' : 'rgba(99,102,241,0.01)');
                ctx.beginPath();
                ctx.moveTo(points[0].x, pad.top + ch);
                drawSmoothLine(points);
                ctx.lineTo(points[points.length - 1].x, pad.top + ch);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();

                ctx.beginPath();
                drawSmoothLine(points);
                ctx.strokeStyle = lineColor;
                ctx.lineWidth = 2.5;
                ctx.lineJoin = 'round';
                ctx.lineCap = 'round';
                ctx.setLineDash([]);
                ctx.stroke();

                points.forEach(p => {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, 3.5, 0, Math.PI * 2);
                    ctx.fillStyle = lineColor;
                    ctx.fill();
                    ctx.strokeStyle = isDark ? '#1f2937' : '#ffffff';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                });

                const self = this;
                canvas.onmousemove = (e) => {
                    const br = canvas.getBoundingClientRect();
                    const mx = e.clientX - br.left;
                    let closest = 0, minDist = Infinity;
                    points.forEach((p, i) => {
                        const d = Math.abs(mx - p.x);
                        if (d < minDist) { minDist = d; closest = i; }
                    });
                    if (minDist < 40) {
                        self.tooltip = { show: true, x: points[closest].x, y: points[closest].y - 10, label: labels[closest], value: values[closest] };
                    } else {
                        self.tooltip.show = false;
                    }
                };
                canvas.onmouseleave = () => { self.tooltip.show = false; };
            }
        };
    }

    function growthLineChart(data) {
        return {
            tooltip: { show: false, x: 0, y: 0, label: '', value: 0 },
            draw() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                const w = rect.width, h = rect.height;
                const pad = { top: 20, right: 16, bottom: 32, left: 44 };
                const cw = w - pad.left - pad.right;
                const ch = h - pad.top - pad.bottom;
                ctx.clearRect(0, 0, w, h);
                const values = data.map(d => d.count);
                const labels = data.map(d => d.date);
                const rawMax = Math.max(...values, 1);
                let niceMax, stepVal;
                const steps = 5;
                if (rawMax <= 5) { niceMax = 5; stepVal = 1; }
                else if (rawMax <= 10) { niceMax = 10; stepVal = 2; }
                else {
                    const mag = Math.pow(10, Math.floor(Math.log10(rawMax)));
                    const n = rawMax / mag;
                    if (n <= 2) niceMax = 2 * mag;
                    else if (n <= 5) niceMax = 5 * mag;
                    else niceMax = 10 * mag;
                    stepVal = niceMax / steps;
                }
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                const textColor = isDark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.4)';
                const lineColor = '#8b5cf6';
                ctx.font = '11px system-ui, sans-serif';
                ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
                for (let i = 0; i <= steps; i++) {
                    const y = pad.top + ch - (i / steps) * ch;
                    ctx.strokeStyle = gridColor; ctx.lineWidth = 1; ctx.setLineDash([]);
                    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(w - pad.right, y); ctx.stroke();
                    ctx.fillStyle = textColor; ctx.fillText(Math.round(i * stepVal).toLocaleString(), pad.left - 8, y);
                }
                ctx.textAlign = 'center'; ctx.textBaseline = 'top';
                const points = [];
                const skip = data.length > 14 ? Math.ceil(data.length / 7) : 1;
                for (let i = 0; i < data.length; i++) {
                    const x = pad.left + (i / Math.max(data.length - 1, 1)) * cw;
                    const y = pad.top + ch - (values[i] / niceMax) * ch;
                    points.push({ x, y });
                    if (i % skip === 0 || i === data.length - 1) { ctx.fillStyle = textColor; ctx.fillText(labels[i], x, pad.top + ch + 10); }
                }
                if (points.length === 0) return;
                function smooth(pts) {
                    if (pts.length < 2) return;
                    ctx.moveTo(pts[0].x, pts[0].y);
                    if (pts.length === 2) { ctx.lineTo(pts[1].x, pts[1].y); return; }
                    for (let i = 0; i < pts.length - 1; i++) {
                        const cpx = (pts[i].x + pts[i+1].x) / 2;
                        ctx.quadraticCurveTo(pts[i].x, pts[i].y, cpx, (pts[i].y + pts[i+1].y) / 2);
                    }
                    ctx.lineTo(pts[pts.length-1].x, pts[pts.length-1].y);
                }
                const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
                grad.addColorStop(0, isDark ? 'rgba(139,92,246,0.25)' : 'rgba(139,92,246,0.15)');
                grad.addColorStop(1, isDark ? 'rgba(139,92,246,0.02)' : 'rgba(139,92,246,0.01)');
                ctx.beginPath(); ctx.moveTo(points[0].x, pad.top + ch); smooth(points);
                ctx.lineTo(points[points.length-1].x, pad.top + ch); ctx.closePath(); ctx.fillStyle = grad; ctx.fill();
                ctx.beginPath(); smooth(points); ctx.strokeStyle = lineColor; ctx.lineWidth = 2.5;
                ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.setLineDash([]); ctx.stroke();
                points.forEach(p => {
                    ctx.beginPath(); ctx.arc(p.x, p.y, 3.5, 0, Math.PI * 2);
                    ctx.fillStyle = lineColor; ctx.fill();
                    ctx.strokeStyle = isDark ? '#1f2937' : '#ffffff'; ctx.lineWidth = 2; ctx.stroke();
                });
                const self = this;
                canvas.onmousemove = (e) => {
                    const br = canvas.getBoundingClientRect(); const mx = e.clientX - br.left;
                    let closest = 0, minDist = Infinity;
                    points.forEach((p, i) => { const d = Math.abs(mx - p.x); if (d < minDist) { minDist = d; closest = i; } });
                    if (minDist < 40) { self.tooltip = { show: true, x: points[closest].x, y: points[closest].y - 10, label: labels[closest], value: values[closest] }; }
                    else { self.tooltip.show = false; }
                };
                canvas.onmouseleave = () => { self.tooltip.show = false; };
            }
        };
    }

    function gscChart(data) {
        return {
            tooltip: { show: false, x: 0, y: 0, label: '' },
            draw() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                const w = rect.width, h = rect.height;
                const pad = { top: 20, right: 16, bottom: 32, left: 44 };
                const cw = w - pad.left - pad.right, ch = h - pad.top - pad.bottom;
                ctx.clearRect(0, 0, w, h);
                const clicks = data.map(d => d.clicks);
                const impressions = data.map(d => d.impressions);
                const labels = data.map(d => d.date);
                const rawMax = Math.max(...clicks, ...impressions, 1);
                let niceMax, stepVal; const steps = 5;
                if (rawMax <= 5) { niceMax = 5; stepVal = 1; }
                else if (rawMax <= 10) { niceMax = 10; stepVal = 2; }
                else { const m = Math.pow(10, Math.floor(Math.log10(rawMax))); const n = rawMax / m;
                    if (n <= 2) niceMax = 2*m; else if (n <= 5) niceMax = 5*m; else niceMax = 10*m; stepVal = niceMax / steps; }
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                const textColor = isDark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.4)';
                ctx.font = '11px system-ui, sans-serif';
                ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
                for (let i = 0; i <= steps; i++) {
                    const y = pad.top + ch - (i / steps) * ch;
                    ctx.strokeStyle = gridColor; ctx.lineWidth = 1; ctx.setLineDash([]);
                    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(w - pad.right, y); ctx.stroke();
                    ctx.fillStyle = textColor; ctx.fillText(Math.round(i * stepVal).toLocaleString(), pad.left - 8, y);
                }
                const barW = Math.max(cw / data.length * 0.35, 4);
                const skip = data.length > 14 ? Math.ceil(data.length / 7) : 1;
                ctx.textAlign = 'center'; ctx.textBaseline = 'top';
                for (let i = 0; i < data.length; i++) {
                    const x = pad.left + (i / Math.max(data.length - 1, 1)) * cw;
                    const cH = (clicks[i] / niceMax) * ch;
                    ctx.fillStyle = isDark ? 'rgba(59,130,246,0.7)' : 'rgba(59,130,246,0.8)';
                    ctx.beginPath(); ctx.roundRect(x - barW, pad.top + ch - cH, barW, cH, [3,3,0,0]); ctx.fill();
                    const iH = (impressions[i] / niceMax) * ch;
                    ctx.fillStyle = isDark ? 'rgba(139,92,246,0.5)' : 'rgba(139,92,246,0.6)';
                    ctx.beginPath(); ctx.roundRect(x, pad.top + ch - iH, barW, iH, [3,3,0,0]); ctx.fill();
                    if (i % skip === 0 || i === data.length - 1) { ctx.fillStyle = textColor; ctx.fillText(labels[i], x, pad.top + ch + 10); }
                }
                const self = this;
                canvas.onmousemove = (e) => {
                    const br = canvas.getBoundingClientRect(); const mx = e.clientX - br.left;
                    let cl = 0, md = Infinity;
                    for (let i = 0; i < data.length; i++) { const x = pad.left + (i / Math.max(data.length-1,1)) * cw; const d = Math.abs(mx - x); if (d < md) { md = d; cl = i; } }
                    if (md < 40) { const x = pad.left + (cl / Math.max(data.length-1,1)) * cw;
                        self.tooltip = { show: true, x, y: pad.top, label: '<strong>'+labels[cl]+'</strong><br>Clicks: '+clicks[cl]+'<br>Impressions: '+impressions[cl]+'<br>Position: '+data[cl].position };
                    } else { self.tooltip.show = false; }
                };
                canvas.onmouseleave = () => { self.tooltip.show = false; };
            }
        };
    }

    function aiUsageChart(data) {
        return {
            tooltip: { show: false, x: 0, y: 0, label: '' },
            draw() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                const w = rect.width, h = rect.height;
                const pad = { top: 20, right: 16, bottom: 32, left: 44 };
                const cw = w - pad.left - pad.right, ch = h - pad.top - pad.bottom;
                ctx.clearRect(0, 0, w, h);
                const requests = data.map(d => d.requests);
                const labels = data.map(d => d.date);
                const rawMax = Math.max(...requests, 1);
                let niceMax, stepVal; const steps = 5;
                if (rawMax <= 5) { niceMax = 5; stepVal = 1; }
                else if (rawMax <= 10) { niceMax = 10; stepVal = 2; }
                else { const m = Math.pow(10, Math.floor(Math.log10(rawMax))); const n = rawMax / m;
                    if (n <= 2) niceMax = 2*m; else if (n <= 5) niceMax = 5*m; else niceMax = 10*m; stepVal = niceMax / steps; }
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                const textColor = isDark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.4)';
                ctx.font = '11px system-ui, sans-serif';
                ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
                for (let i = 0; i <= steps; i++) {
                    const y = pad.top + ch - (i / steps) * ch;
                    ctx.strokeStyle = gridColor; ctx.lineWidth = 1; ctx.setLineDash([]);
                    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(w - pad.right, y); ctx.stroke();
                    ctx.fillStyle = textColor; ctx.fillText(Math.round(i * stepVal).toLocaleString(), pad.left - 8, y);
                }
                const barW = Math.max(cw / data.length * 0.6, 6);
                const skip = data.length > 14 ? Math.ceil(data.length / 7) : 1;
                ctx.textAlign = 'center'; ctx.textBaseline = 'top';
                for (let i = 0; i < data.length; i++) {
                    const x = pad.left + (i / Math.max(data.length - 1, 1)) * cw;
                    const rH = (requests[i] / niceMax) * ch;
                    const grad = ctx.createLinearGradient(0, pad.top + ch - rH, 0, pad.top + ch);
                    grad.addColorStop(0, isDark ? 'rgba(99,102,241,0.8)' : 'rgba(99,102,241,0.9)');
                    grad.addColorStop(1, isDark ? 'rgba(99,102,241,0.3)' : 'rgba(99,102,241,0.4)');
                    ctx.fillStyle = grad;
                    ctx.beginPath(); ctx.roundRect(x - barW/2, pad.top + ch - rH, barW, rH, [4,4,0,0]); ctx.fill();
                    if (i % skip === 0 || i === data.length - 1) { ctx.fillStyle = textColor; ctx.fillText(labels[i], x, pad.top + ch + 10); }
                }
                const self = this;
                canvas.onmousemove = (e) => {
                    const br = canvas.getBoundingClientRect(); const mx = e.clientX - br.left;
                    let cl = 0, md = Infinity;
                    for (let i = 0; i < data.length; i++) { const x = pad.left + (i / Math.max(data.length-1,1)) * cw; const d = Math.abs(mx - x); if (d < md) { md = d; cl = i; } }
                    if (md < 40) { const x = pad.left + (cl / Math.max(data.length-1,1)) * cw;
                        self.tooltip = { show: true, x, y: pad.top, label: '<strong>'+labels[cl]+'</strong><br>Requests: '+requests[cl]+'<br>Tokens: '+data[cl].tokens.toLocaleString()+'<br>Cost: $'+data[cl].cost.toFixed(4) };
                    } else { self.tooltip.show = false; }
                };
                canvas.onmouseleave = () => { self.tooltip.show = false; };
            }
        };
    }

    function trafficChart(data) {
        return {
            tooltip: { show: false, x: 0, y: 0, label: '' },
            draw() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                ctx.scale(dpr, dpr);
                const w = rect.width, h = rect.height;
                const pad = { top: 20, right: 16, bottom: 32, left: 44 };
                const cw = w - pad.left - pad.right;
                const ch = h - pad.top - pad.bottom;

                ctx.clearRect(0, 0, w, h);

                const views = data.map(d => d.views);
                const unique = data.map(d => d.unique);
                const labels = data.map(d => d.date);
                const rawMax = Math.max(...views, ...unique, 1);

                let niceMax, stepVal;
                const steps = 5;
                if (rawMax <= 5) { niceMax = 5; stepVal = 1; }
                else if (rawMax <= 10) { niceMax = 10; stepVal = 2; }
                else {
                    const magnitude = Math.pow(10, Math.floor(Math.log10(rawMax)));
                    const normalized = rawMax / magnitude;
                    if (normalized <= 2) niceMax = 2 * magnitude;
                    else if (normalized <= 5) niceMax = 5 * magnitude;
                    else niceMax = 10 * magnitude;
                    stepVal = niceMax / steps;
                }

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
                const textColor = isDark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.4)';

                ctx.font = '11px system-ui, sans-serif';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';
                for (let i = 0; i <= steps; i++) {
                    const y = pad.top + ch - (i / steps) * ch;
                    ctx.strokeStyle = gridColor;
                    ctx.lineWidth = 1;
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.moveTo(pad.left, y);
                    ctx.lineTo(w - pad.right, y);
                    ctx.stroke();
                    ctx.fillStyle = textColor;
                    ctx.fillText(Math.round(i * stepVal).toLocaleString(), pad.left - 8, y);
                }

                const barWidth = Math.max(cw / data.length * 0.35, 4);
                const skipInterval = data.length > 14 ? Math.ceil(data.length / 7) : 1;

                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';

                for (let i = 0; i < data.length; i++) {
                    const x = pad.left + (i / Math.max(data.length - 1, 1)) * cw;

                    // Total views bar
                    const viewH = (views[i] / niceMax) * ch;
                    ctx.fillStyle = isDark ? 'rgba(99,102,241,0.7)' : 'rgba(99,102,241,0.8)';
                    ctx.beginPath();
                    ctx.roundRect(x - barWidth, pad.top + ch - viewH, barWidth, viewH, [3, 3, 0, 0]);
                    ctx.fill();

                    // Unique visitors bar
                    const uniqueH = (unique[i] / niceMax) * ch;
                    ctx.fillStyle = isDark ? 'rgba(34,197,94,0.7)' : 'rgba(34,197,94,0.8)';
                    ctx.beginPath();
                    ctx.roundRect(x, pad.top + ch - uniqueH, barWidth, uniqueH, [3, 3, 0, 0]);
                    ctx.fill();

                    if (i % skipInterval === 0 || i === data.length - 1) {
                        ctx.fillStyle = textColor;
                        ctx.fillText(labels[i], x, pad.top + ch + 10);
                    }
                }

                const self = this;
                canvas.onmousemove = (e) => {
                    const br = canvas.getBoundingClientRect();
                    const mx = e.clientX - br.left;
                    let closest = 0, minDist = Infinity;
                    for (let i = 0; i < data.length; i++) {
                        const x = pad.left + (i / Math.max(data.length - 1, 1)) * cw;
                        const d = Math.abs(mx - x);
                        if (d < minDist) { minDist = d; closest = i; }
                    }
                    if (minDist < 40) {
                        const x = pad.left + (closest / Math.max(data.length - 1, 1)) * cw;
                        self.tooltip = {
                            show: true,
                            x: x,
                            y: pad.top,
                            label: '<strong>' + labels[closest] + '</strong><br>Views: ' + views[closest] + '<br>Unique: ' + unique[closest]
                        };
                    } else {
                        self.tooltip.show = false;
                    }
                };
                canvas.onmouseleave = () => { self.tooltip.show = false; };
            }
        };
    }
    </script>
</div>
