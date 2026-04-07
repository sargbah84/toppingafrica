<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Logs</h1>
        <button type="button" @click="window.tcModal.confirm('Are you sure you want to clear all activity logs?', {variant:'danger', confirmText:'Clear all'}).then(ok => ok && $wire.clearLogs())" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30">
            Clear All
        </button>
    </div>

    @if (session('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search activities..."
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <select wire:model.live="logName" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Log Types</option>
                @foreach ($logNames as $name)
                    <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                @endforeach
            </select>
            <select wire:model.live="event" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Events</option>
                @foreach ($events as $e)
                    <option value="{{ $e }}">{{ ucfirst($e) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">When</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ $log->created_at->diffForHumans() }}
                                <span class="block text-gray-400 dark:text-gray-500">{{ $log->created_at->format('M d, H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($log->causer)
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $log->causer->name }}</span>
                                @else
                                    <span class="text-gray-400">System</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $eventColors = [
                                        'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $eventColors[$log->event] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ ucfirst($log->event ?? $log->log_name) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                @if ($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($log->properties && $log->properties->has('attributes'))
                                    <details class="cursor-pointer">
                                        <summary class="text-indigo-600 dark:text-indigo-400 hover:underline">View changes</summary>
                                        <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs max-w-xs overflow-auto">
                                            @if ($log->properties->has('old'))
                                                <p class="text-red-500 dark:text-red-400">Old: {{ json_encode($log->properties['old'], JSON_PRETTY_PRINT) }}</p>
                                            @endif
                                            <p class="text-green-500 dark:text-green-400">New: {{ json_encode($log->properties['attributes'], JSON_PRETTY_PRINT) }}</p>
                                        </div>
                                    </details>
                                @elseif ($log->properties && $log->properties->count() > 0)
                                    <details class="cursor-pointer">
                                        <summary class="text-indigo-600 dark:text-indigo-400 hover:underline">Details</summary>
                                        <div class="mt-1 p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs max-w-xs overflow-auto">
                                            {{ json_encode($log->properties, JSON_PRETTY_PRINT) }}
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No activity logs yet. Actions like creating posts, editing content, and logging in will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $logs->links() }}
        </div>
    </div>
</div>
