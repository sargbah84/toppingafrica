<div>
    @if($this->comments->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium">No comments yet</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Comments you leave on articles will appear here.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->comments as $comment)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    {{-- Comment header --}}
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                            {{-- Status badge --}}
                            @if($comment->status === 'approved')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                    Approved
                                </span>
                            @elseif($comment->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd"/></svg>
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                    Spam
                                </span>
                            @endif

                            <span>&middot;</span>
                            <span>{{ $comment->created_at->diffForHumans() }}</span>
                        </div>

                        {{-- Edit button --}}
                        @if($editingId !== $comment->id && $comment->status !== 'spam')
                            <button wire:click="startEditing({{ $comment->id }})"
                                    class="text-xs font-medium text-gray-400 hover:text-primary transition-colors">
                                Edit
                            </button>
                        @endif
                    </div>

                    {{-- On which article --}}
                    @if($comment->post)
                        <a href="{{ route('blog.show', $comment->post->slug) }}#comment-{{ $comment->id }}"
                           class="text-xs font-medium text-primary hover:text-primary-hover transition-colors mb-2 inline-block">
                            on: {{ $comment->post->title }}
                        </a>
                    @endif

                    {{-- Comment body or edit form --}}
                    @if($editingId === $comment->id)
                        <div class="mt-2">
                            <textarea wire:model="editBody" rows="3"
                                      class="block w-full rounded-md border-0 py-2 text-sm text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary"></textarea>
                            @error('editBody')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <div class="flex items-center gap-2 justify-end mt-2">
                                <button wire:click="cancelEditing"
                                        class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                    Cancel
                                </button>
                                <button type="submit" wire:click="saveEdit"
                                        class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-md hover:bg-primary-hover transition-colors">
                                    Save
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                            {!! nl2br(e($comment->body)) !!}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($this->hasMore)
            <div class="mt-6 text-center">
                <button wire:click="loadMore"
                        class="inline-flex items-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="loadMore">Load More</span>
                    <span wire:loading wire:target="loadMore">Loading...</span>
                </button>
            </div>
        @endif
    @endif
</div>
