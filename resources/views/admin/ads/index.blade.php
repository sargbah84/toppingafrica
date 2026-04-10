<x-layouts.admin title="Ads" header="Ad Management">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage advertisement placements across the site.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('admin.ads.create') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Ad
                </a>
            </div>
        </div>

        {{-- Position Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow px-4 py-4 sm:px-6">
            <form method="GET" action="{{ route('admin.ads.index') }}" class="sm:flex sm:items-center sm:gap-4">
                <div>
                    <select name="position"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option value="">All Positions</option>
                        @foreach(['header' => 'Header', 'sidebar' => 'Sidebar', 'in_article' => 'In Article', 'after_content' => 'After Content', 'footer' => 'Footer'] as $value => $label)
                            <option value="{{ $value }}" {{ request('position') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-2 sm:mt-0 flex gap-2">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Filter
                    </button>
                    @if(request('position'))
                        <a href="{{ route('admin.ads.index') }}"
                           class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Ads Table --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Schedule</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($ads as $ad)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $ad->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $positionColors = [
                                        'header' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                        'sidebar' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
                                        'in_article' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
                                        'after_content' => 'bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300',
                                        'footer' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
                                    ];
                                    $positionLabels = [
                                        'header' => 'Header',
                                        'sidebar' => 'Sidebar',
                                        'in_article' => 'In Article',
                                        'after_content' => 'After Content',
                                        'footer' => 'Footer',
                                    ];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $positionColors[$ad->position] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $positionLabels[$ad->position] ?? ucfirst($ad->position) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $ad->code ? 'HTML/Code' : ($ad->image ? 'Image' : 'None') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($ad->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                @if($ad->starts_at || $ad->ends_at)
                                    {{ $ad->starts_at?->format('M d') ?? 'Start' }} - {{ $ad->ends_at?->format('M d, Y') ?? 'No end' }}
                                @else
                                    Always
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $ad->order }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.ads.edit', $ad) }}"
                                       class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Edit</a>
                                    <form method="POST" action="{{ route('admin.ads.duplicate', $ad) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">Duplicate</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.ads.destroy', $ad) }}" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                @click="window.tcModal.confirm('Are you sure you want to delete this ad?', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $el.closest('form').submit())"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No ads found. <a href="{{ route('admin.ads.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create your first ad.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($ads->hasPages())
                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">
                    {{ $ads->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
