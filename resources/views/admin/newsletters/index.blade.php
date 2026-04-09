<x-layouts.admin title="Newsletter Subscribers" header="Newsletter Subscribers">
    <div class="space-y-6" x-data="{
        detail: null,
        loading: false,
        async viewSubscriber(id) {
            this.loading = true;
            this.detail = {};
            try {
                const res = await fetch('/admin/newsletters/' + id);
                this.detail = await res.json();
            } catch (e) {
                this.detail = null;
            }
            this.loading = false;
        }
    }">
        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Subscribers</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</p>
                <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['subscribed']) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unsubscribed</p>
                <p class="mt-1 text-2xl font-bold text-gray-500 dark:text-gray-400">{{ number_format($stats['unsubscribed']) }}</p>
            </div>
        </div>

        {{-- Header with Export --}}
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage newsletter subscribers.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('admin.newsletters.export') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-4 py-4 sm:px-6">
            <form method="GET" action="{{ route('admin.newsletters.index') }}" class="sm:flex sm:items-center sm:gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by email or name..."
                           class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>
                <div class="mt-2 sm:mt-0">
                    <select name="status"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option value="">All Status</option>
                        <option value="subscribed" {{ request('status') === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                        <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                    </select>
                </div>
                <div class="mt-2 sm:mt-0 flex gap-2">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Filter
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('admin.newsletters.index') }}"
                           class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Subscribers Table --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subscribed</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($subscribers as $subscriber)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button type="button" @click="viewSubscriber({{ $subscriber->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 hover:underline">
                                    {{ $subscriber->email }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $subscriber->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($subscriber->status === 'subscribed')
                                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">Subscribed</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Unsubscribed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $subscriber->subscribed_at?->format('M d, Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="viewSubscriber({{ $subscriber->id }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View</button>
                                    <form method="POST" action="{{ route('admin.newsletters.destroy', $subscriber) }}" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                @click="window.tcModal.confirm('Are you sure you want to delete this subscriber?', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $el.closest('form').submit())"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No subscribers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($subscribers->hasPages())
                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">
                    {{ $subscribers->links() }}
                </div>
            @endif
        </div>
        {{-- Subscriber Detail Modal --}}
        <template x-teleport="body">
            <div x-show="detail !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="detail !== null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         @click="detail = null" class="fixed inset-0 bg-black/50"></div>

                    <div x-show="detail !== null" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg overflow-hidden">

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Subscriber Details</h3>
                            <button @click="detail = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Loading --}}
                        <div x-show="loading" class="px-6 py-12 text-center">
                            <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">Loading subscriber info...</p>
                        </div>

                        {{-- Content --}}
                        <div x-show="!loading && detail" class="px-6 py-5 space-y-4">
                            {{-- Contact Info --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</p>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white break-all" x-text="detail?.email"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</p>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" x-text="detail?.name || '—'"></p>
                                </div>
                            </div>

                            {{-- Status & Dates --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</p>
                                    <p class="mt-1">
                                        <span x-show="detail?.status === 'subscribed'" class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">Subscribed</span>
                                        <span x-show="detail?.status !== 'subscribed'" class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Unsubscribed</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subscribed At</p>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" x-text="detail?.subscribed_at || '—'"></p>
                                </div>
                            </div>

                            {{-- Device Info --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Device Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">IP Address</p>
                                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white font-mono" x-text="detail?.ip_address || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">User Agent</p>
                                        <p class="mt-0.5 text-xs text-gray-700 dark:text-gray-300 break-all leading-relaxed" x-text="detail?.user_agent || '—'"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Location (from IP lookup) --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Location (IP Lookup)</h4>
                                <template x-if="detail?.location">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Country</p>
                                            <p class="mt-0.5 text-sm text-gray-900 dark:text-white" x-text="detail.location.country"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Region</p>
                                            <p class="mt-0.5 text-sm text-gray-900 dark:text-white" x-text="detail.location.regionName"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">City</p>
                                            <p class="mt-0.5 text-sm text-gray-900 dark:text-white" x-text="detail.location.city"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">ISP</p>
                                            <p class="mt-0.5 text-sm text-gray-900 dark:text-white" x-text="detail.location.isp"></p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!detail?.location">
                                    <p class="text-sm text-gray-400 dark:text-gray-500">
                                        <span x-show="!detail?.ip_address">No IP address recorded.</span>
                                        <span x-show="detail?.ip_address">Location lookup unavailable.</span>
                                    </p>
                                </template>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                            <button @click="detail = null"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.admin>
