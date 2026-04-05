<div x-data="{ showModal: false, selected: null }">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Request Logs</h1>
        <div class="flex items-center gap-2">
            <button wire:click="pruneOldLogs" wire:confirm="Delete logs older than 30 days?" class="px-4 py-2 text-sm font-medium text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 dark:text-yellow-400 rounded-lg hover:bg-yellow-100">
                Prune Old
            </button>
            <button wire:click="clearLogs" wire:confirm="Are you sure you want to clear all request logs?" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg hover:bg-red-100">
                Clear All
            </button>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total Requests</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Errors</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($stats['errors']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Error Rate</p>
            <p class="text-2xl font-bold {{ $stats['error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-1">{{ $stats['error_rate'] }}%</p>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Avg Response</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['avg_time']) }}ms</p>
        </div>
    </div>

    {{-- Top Errors --}}
    @if ($stats['top_errors']->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4 mb-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Top Recurring Errors</h3>
            <div class="space-y-2">
                @foreach ($stats['top_errors'] as $error)
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $error->status_code >= 500 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                {{ $error->status_code }}
                            </span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ Str::limit($error->url, 80) }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white flex-shrink-0">{{ $error->count }}x</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search URL..."
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Statuses</option>
                <option value="success">Success (2xx/3xx)</option>
                <option value="errors">All Errors (4xx/5xx)</option>
                <option value="4xx">Client Errors (4xx)</option>
                <option value="5xx">Server Errors (5xx)</option>
            </select>
            <select wire:model.live="methodFilter" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Methods</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
            </select>
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">URL</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($logs as $log)
                        @php
                            $statusColor = match (true) {
                                $log->status_code >= 500 => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                $log->status_code >= 400 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                $log->status_code >= 300 => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                default => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            };
                            $methodColor = match ($log->method) {
                                'GET' => 'text-green-600 dark:text-green-400',
                                'POST' => 'text-blue-600 dark:text-blue-400',
                                'PUT', 'PATCH' => 'text-yellow-600 dark:text-yellow-400',
                                'DELETE' => 'text-red-600 dark:text-red-400',
                                default => 'text-gray-600 dark:text-gray-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer {{ $log->status_code >= 500 ? 'bg-red-50/50 dark:bg-red-900/5' : '' }}"
                            @click="selected = {{ Js::from([
                                'id' => $log->id,
                                'method' => $log->method,
                                'url' => $log->url,
                                'status_code' => $log->status_code,
                                'ip_address' => $log->ip_address,
                                'user_agent' => $log->user_agent,
                                'user_name' => $log->user?->name ?? 'Guest',
                                'response_time_ms' => $log->response_time_ms,
                                'error_message' => $log->error_message,
                                'exception' => $log->exception,
                                'response_body' => $log->response_body ? Str::limit($log->response_body, 3000) : null,
                                'route_name' => $log->route_name,
                                'created_at' => $log->created_at->format('M d, Y H:i:s'),
                            ]) }}; showModal = true">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold {{ $methodColor }}">{{ $log->method }}</span>
                                    <span class="text-gray-700 dark:text-gray-300 truncate max-w-xs" title="{{ $log->url }}">
                                        /{{ ltrim(parse_url($log->url, PHP_URL_PATH) ?? '', '/') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $statusColor }}">{{ $log->status_code }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right text-xs {{ ($log->response_time_ms ?? 0) > 1000 ? 'text-red-500 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $log->response_time_ms ? $log->response_time_ms . 'ms' : '—' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ $log->user?->name ?? 'Guest' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ $log->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No request logs yet. Logs are captured automatically for errors and sampled for successful requests.
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

    {{-- Detail Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
        <div class="flex items-start justify-center min-h-screen px-4 pt-16 pb-20">
            {{-- Backdrop --}}
            <div x-show="showModal" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/50" @click="showModal = false"></div>

            {{-- Modal --}}
            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                 class="relative z-10 w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span x-show="selected" class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold"
                              :class="{
                                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': selected?.status_code >= 500,
                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': selected?.status_code >= 400 && selected?.status_code < 500,
                                  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': selected?.status_code < 400,
                              }"
                              x-text="selected?.status_code"></span>
                        <span x-show="selected" class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-bold"
                              :class="{
                                  'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400': selected?.method === 'GET',
                                  'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400': selected?.method === 'POST',
                                  'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400': selected?.method === 'PUT' || selected?.method === 'PATCH',
                                  'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400': selected?.method === 'DELETE',
                              }"
                              x-text="selected?.method"></span>
                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300 truncate max-w-xs" x-text="selected ? '/' + selected.url.replace(/^https?:\/\/[^\/]+\//, '') : ''"></span>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                    {{-- Request Info --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Request Info</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 font-medium">Timestamp</p>
                                <p class="text-sm text-gray-900 dark:text-white" x-text="selected?.created_at"></p>
                            </div>
                            <div>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 font-medium">Duration</p>
                                <p class="text-sm text-gray-900 dark:text-white" x-text="selected?.response_time_ms ? selected.response_time_ms + 'ms' : '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 font-medium">IP Address</p>
                                <p class="text-sm text-gray-900 dark:text-white" x-text="selected?.ip_address || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 font-medium">User</p>
                                <p class="text-sm text-gray-900 dark:text-white" x-text="selected?.user_name || 'Guest'"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Full URL --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Full URL</h4>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                            <code class="text-sm text-gray-800 dark:text-gray-200 break-all" x-text="selected?.url"></code>
                        </div>
                    </div>

                    {{-- Exception --}}
                    <template x-if="selected?.exception">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Exception</h4>
                            <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30 rounded-lg p-3">
                                <p class="text-sm font-semibold text-red-700 dark:text-red-400" x-text="selected.exception.split('\n')[0]"></p>
                                <p class="text-sm text-red-600 dark:text-red-300 mt-1" x-text="selected.error_message"></p>
                            </div>
                        </div>
                    </template>

                    {{-- Response Body --}}
                    <template x-if="selected?.response_body">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Response Body (Truncated)</h4>
                            <div class="bg-gray-900 rounded-lg p-4 max-h-60 overflow-auto">
                                <pre class="text-xs text-gray-300 whitespace-pre-wrap break-all" x-text="selected.response_body"></pre>
                            </div>
                        </div>
                    </template>

                    {{-- User Agent --}}
                    <template x-if="selected?.user_agent">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">User Agent</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                                <p class="text-xs text-gray-600 dark:text-gray-400 break-all" x-text="selected.user_agent"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
