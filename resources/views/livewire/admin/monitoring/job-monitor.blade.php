<div
    @if($autoRefresh) wire:poll.10s @endif
>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Job Monitor</h1>
    </div>

    @if (session('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Status Bar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
        {{-- Scheduler Status --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scheduler</p>
                @if ($schedulerStatus['running'])
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5 inline animate-spin text-green-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </span>
                @else
                    <button wire:click="runScheduler" wire:confirm="Run the scheduler manually?" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                        Run Now
                    </button>
                @endif
            </div>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $schedulerStatus['running'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-lg font-bold {{ $schedulerStatus['running'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $schedulerStatus['running'] ? 'Running' : 'Stopped' }}
                </span>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ $schedulerStatus['last_heartbeat'] ? 'Last: ' . $schedulerStatus['last_heartbeat'] : 'No heartbeat detected' }}
            </p>
        </div>

        {{-- Queue Worker Status --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Queue Worker</p>
                <button wire:click="restartQueueWorker" wire:confirm="Send restart signal to queue workers?" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/></svg>
                    Restart
                </button>
            </div>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $queueStatus['running'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-lg font-bold {{ $queueStatus['running'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $queueStatus['running'] ? 'Running' : 'Stopped' }}
                </span>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $queueStatus['pending'] }} pending in queue</p>
        </div>

        {{-- Completed Today --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed Today</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['completed_today']) }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                Avg: {{ number_format($stats['avg_duration']) }}ms
            </p>
        </div>

        {{-- Failed 24H --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Failed (24H)</p>
            <p class="text-2xl font-bold {{ $stats['failed_24h'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} mt-1">
                {{ number_format($stats['failed_24h']) }}
            </p>
        </div>
    </div>

    {{-- Controls Row --}}
    <div class="flex items-center justify-between mb-4">
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer select-none">
            <input type="checkbox" wire:click="toggleAutoRefresh" @checked($autoRefresh) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
            Auto-refresh (10s)
        </label>
        <div class="flex items-center gap-2">
            <button wire:click="processScheduledPosts" wire:confirm="Publish all posts that are past their scheduled date?" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                Process Scheduled Posts
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-6 overflow-x-auto">
        @php
            $tabs = [
                'overview' => ['label' => 'Overview', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green'],
                'queued' => ['label' => 'Queued Jobs', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow'],
                'failed' => ['label' => 'Failed Jobs', 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z', 'color' => 'red'],
                'history' => ['label' => 'Job History', 'icon' => 'M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3', 'color' => 'indigo'],
                'scheduled' => ['label' => 'Scheduled Tasks', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5', 'color' => 'purple'],
                'config' => ['label' => 'Schedule Config', 'icon' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75', 'color' => 'gray'],
            ];
        @endphp
        @foreach ($tabs as $key => $tab)
            <button
                wire:click="setTab('{{ $key }}')"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-full whitespace-nowrap transition
                    {{ $activeTab === $key
                        ? 'bg-' . $tab['color'] . '-600 text-white shadow-sm'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/></svg>
                {{ $tab['label'] }}
                @if ($key === 'failed' && $failedCount > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold {{ $activeTab === $key ? 'bg-white/20' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }} rounded-full">{{ $failedCount }}</span>
                @endif
                @if ($key === 'queued' && $stats['pending'] > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold {{ $activeTab === $key ? 'bg-white/20' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' }} rounded-full">{{ $stats['pending'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Tab Content --}}

    {{-- OVERVIEW TAB --}}
    @if ($activeTab === 'overview')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Recent Job Activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($recentActivity as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $activity->finished_at?->format('M d, H:i:s') }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ class_basename($activity->job_name) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($activity->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Completed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity->duration_ms !== null ? number_format($activity->duration_ms) . 'ms' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No recent job activity recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- QUEUED JOBS TAB --}}
    @elseif ($activeTab === 'queued')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Queued Jobs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Queue</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Attempts</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Available At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($queuedJobs as $job)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $job->display_name }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $job->queue }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $job->attempts_count > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                        {{ $job->attempts_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($job->is_reserved)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Processing</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">Waiting</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $job->created->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $job->available->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No pending jobs in the queue.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- FAILED JOBS TAB --}}
    @elseif ($activeTab === 'failed')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Failed Jobs</h3>
                @if ($failedCount > 0)
                    <div class="flex items-center gap-2">
                        <button wire:click="retryAllFailed" wire:confirm="Retry all failed jobs?" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition">
                            Retry All
                        </button>
                        <button wire:click="flushFailedJobs" wire:confirm="Delete all failed jobs permanently?" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                            Flush All
                        </button>
                    </div>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Queue</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Failed At</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Exception</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($failedJobs as $job)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ class_basename($job->display_name) }}</span>
                                    <span class="block text-xs text-gray-400 mt-0.5">{{ Str::limit($job->uuid, 20) }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $job->queue ?? 'default' }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $job->failed_date->diffForHumans() }}</td>
                                <td class="px-4 py-3">
                                    <details class="cursor-pointer">
                                        <summary class="text-xs text-red-600 dark:text-red-400 truncate max-w-xs">
                                            {{ Str::limit($job->short_exception, 80) }}
                                        </summary>
                                        <pre class="mt-2 p-2 bg-gray-900 text-red-300 text-xs rounded-lg overflow-auto max-h-48 max-w-lg">{{ $job->exception }}</pre>
                                    </details>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button wire:click="retryFailedJob('{{ $job->uuid }}')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mr-2">Retry</button>
                                    <button wire:click="deleteFailedJob('{{ $job->uuid }}')" wire:confirm="Delete this failed job?" class="text-xs text-red-600 dark:text-red-400 hover:underline">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No failed jobs. All queue jobs are running successfully.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- JOB HISTORY TAB --}}
    @elseif ($activeTab === 'history')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Job History</h3>
                @if ($jobHistory->isNotEmpty())
                    <button wire:click="clearJobHistory" wire:confirm="Clear all job history?" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                        Clear History
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Queue</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($jobHistory as $record)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $record->finished_at?->format('M d, H:i:s') }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ class_basename($record->job_name) }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $record->queue }}</td>
                                <td class="px-4 py-3">
                                    @if ($record->status === 'completed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Completed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->duration_ms !== null ? number_format($record->duration_ms) . 'ms' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No job history recorded yet. History is populated as jobs complete.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- SCHEDULED TASKS TAB --}}
    @elseif ($activeTab === 'scheduled')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Scheduled Tasks</h3>
            @if (count($scheduledTasks) > 0)
                <div class="space-y-2">
                    @foreach ($scheduledTasks as $task)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm text-gray-700 dark:text-gray-300 font-mono">
                            {{ $task }}
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No scheduled tasks configured. Add tasks to <code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-xs">routes/console.php</code>.</p>
            @endif
        </div>

    {{-- SCHEDULE CONFIG TAB --}}
    @elseif ($activeTab === 'config')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Queue & Schedule Configuration</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($scheduleConfig as $key => $value)
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($key)) }}</span>
                        <span class="text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50 px-2.5 py-1 rounded">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
