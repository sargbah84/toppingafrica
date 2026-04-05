<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Job Monitor</h1>
    </div>

    @if (session('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Overview Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
        <button wire:click="setSection('pending')" class="text-left bg-white dark:bg-gray-800 shadow rounded-xl p-4 hover:ring-2 hover:ring-indigo-500 transition {{ $activeSection === 'pending' ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Pending Jobs</p>
            <p class="text-2xl font-bold {{ $overview['pending'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }} mt-1">{{ number_format($overview['pending']) }}</p>
        </button>
        <button wire:click="setSection('failed')" class="text-left bg-white dark:bg-gray-800 shadow rounded-xl p-4 hover:ring-2 hover:ring-indigo-500 transition {{ $activeSection === 'failed' ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Failed Jobs</p>
            <p class="text-2xl font-bold {{ $overview['failed'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-1">{{ number_format($overview['failed']) }}</p>
        </button>
        <button wire:click="setSection('batches')" class="text-left bg-white dark:bg-gray-800 shadow rounded-xl p-4 hover:ring-2 hover:ring-indigo-500 transition {{ $activeSection === 'batches' ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Job Batches</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($overview['batches']) }}</p>
        </button>
        <button wire:click="setSection('overview')" class="text-left bg-white dark:bg-gray-800 shadow rounded-xl p-4 hover:ring-2 hover:ring-indigo-500 transition {{ $activeSection === 'overview' ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Scheduled Tasks</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ count($scheduledTasks) }}</p>
        </button>
    </div>

    {{-- Overview Section --}}
    @if ($activeSection === 'overview')
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
                <p class="text-sm text-gray-500 dark:text-gray-400">No scheduled tasks configured. Add tasks to <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">routes/console.php</code>.</p>
            @endif
        </div>

    {{-- Pending Jobs Section --}}
    @elseif ($activeSection === 'pending')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Pending Jobs in Queue</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Queue</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Attempts</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Available At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($pendingJobs as $job)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ class_basename($job->display_name) }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $job->queue }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $job->attempts_count > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                        {{ $job->attempts_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $job->created->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $job->available->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No pending jobs in the queue.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- Failed Jobs Section --}}
    @elseif ($activeSection === 'failed')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Failed Jobs</h3>
                @if ($overview['failed'] > 0)
                    <div class="flex items-center gap-2">
                        <button wire:click="retryAllFailed" wire:confirm="Retry all failed jobs?" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 rounded-lg hover:bg-indigo-100">
                            Retry All
                        </button>
                        <button wire:click="flushFailedJobs" wire:confirm="Delete all failed jobs permanently?" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg hover:bg-red-100">
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

    {{-- Batches Section --}}
    @elseif ($activeSection === 'batches')
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Job Batches</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Batch</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pending</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Failed</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Progress</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($batches as $batch)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $batch->name ?? 'Unnamed' }}</span>
                                    <span class="block text-xs text-gray-400">{{ Str::limit($batch->id, 20) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $batch->total_jobs }}</td>
                                <td class="px-4 py-3 text-center {{ $batch->pending_jobs > 0 ? 'text-yellow-600 dark:text-yellow-400 font-medium' : 'text-gray-500' }}">{{ $batch->pending_jobs }}</td>
                                <td class="px-4 py-3 text-center {{ $batch->failed_jobs > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500' }}">{{ $batch->failed_jobs }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="{{ $batch->progress >= 100 ? 'bg-green-500' : 'bg-indigo-500' }} h-2 rounded-full" style="width: {{ $batch->progress }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $batch->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $batch->created->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No job batches found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
