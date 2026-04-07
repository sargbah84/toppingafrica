<div class="space-y-6">
    {{-- Status Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'spam' => 'Spam'] as $key => $label)
                <button wire:click="$set('status', '{{ $key }}')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors
                            {{ $status === $key
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    {{ $label }}
                    <span class="ml-2 rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $status === $key
                            ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                        {{ $this->statusCounts[$key] }}
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Search & Bulk Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex-1 max-w-sm">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search comments..."
                   class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
        </div>

        @if(count($selected) > 0)
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($selected) }} selected</span>
                <button type="button" @click="window.tcModal.confirm('Approve selected comments?', {variant:'default', confirmText:'Approve'}).then(ok => ok && $wire.bulkApprove())"
                        class="inline-flex items-center rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-green-500">
                    Approve
                </button>
                <button type="button" @click="window.tcModal.confirm('Mark selected as spam?', {variant:'warning', confirmText:'Mark spam'}).then(ok => ok && $wire.bulkSpam())"
                        class="inline-flex items-center rounded-md bg-yellow-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-yellow-500">
                    Spam
                </button>
                <button type="button" @click="window.tcModal.confirm('Delete selected comments? This cannot be undone.', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.bulkDelete())"
                        class="inline-flex items-center rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500">
                    Delete
                </button>
            </div>
        @endif
    </div>

    {{-- Comments Table --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="w-12 px-4 py-3">
                        <input type="checkbox" wire:model.live="selectAll"
                               class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600 dark:bg-gray-700">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Author</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comment</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Post</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->comments as $comment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-4">
                            <input type="checkbox" wire:model.live="selected" value="{{ $comment->id }}"
                                   class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600 dark:bg-gray-700">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->author_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->user?->email ?? $comment->guest_email }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ Str::limit($comment->body, 100) }}</p>
                            @if($comment->parent_id)
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">Reply to comment</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($comment->post)
                                <a href="{{ route('blog.show', $comment->post->slug) }}" target="_blank"
                                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline max-w-[200px] truncate block">
                                    {{ Str::limit($comment->post->title, 40) }}
                                </a>
                            @else
                                <span class="text-sm text-gray-400">Deleted post</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            @switch($comment->status)
                                @case('approved')
                                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">Approved</span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:text-yellow-300">Pending</span>
                                    @break
                                @case('spam')
                                    <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-300">Spam</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $comment->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                @if($comment->status !== 'approved')
                                    <button wire:click="approve({{ $comment->id }})"
                                            class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300"
                                            title="Approve">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </button>
                                @endif
                                @if($comment->status !== 'spam')
                                    <button wire:click="markSpam({{ $comment->id }})"
                                            class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300"
                                            title="Mark as Spam">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                    </button>
                                @endif
                                <button type="button"
                                        @click="window.tcModal.confirm('Are you sure you want to delete this comment?', {variant:'danger', confirmText:'Delete'}).then(ok => ok && $wire.deleteComment({{ $comment->id }}))"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            No comments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($this->comments->hasPages())
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                {{ $this->comments->links() }}
            </div>
        @endif
    </div>
</div>
