<div>
    @if($this->bookmarks->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium">No bookmarks yet</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Save articles you want to read later.</p>
            <a href="{{ route('home') }}" class="inline-block mt-4 text-sm font-semibold text-primary hover:text-primary-hover transition-colors">
                Browse articles
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->bookmarks as $bookmark)
                <div class="flex gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 group">
                    <a href="{{ route('blog.show', $bookmark->post->slug) }}" class="flex gap-4 flex-1 min-w-0">
                        {{-- Thumbnail --}}
                        <div class="flex-shrink-0 w-24 h-16 sm:w-32 sm:h-20 rounded-md overflow-hidden bg-gray-100 dark:bg-gray-700">
                            @if($bookmark->post->featured_image_url)
                                <img src="{{ $bookmark->post->featured_image_url }}" alt="{{ $bookmark->post->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a2.25 2.25 0 0 0 2.25-2.25V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/></svg>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2">
                                {{ $bookmark->post->title }}
                            </h3>
                            <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                                @if($bookmark->post->categories->isNotEmpty())
                                    <span class="text-primary font-medium">{{ $bookmark->post->categories->first()->name }}</span>
                                    <span>&middot;</span>
                                @endif
                                <span>{{ $bookmark->post->formatted_reading_time }}</span>
                                <span>&middot;</span>
                                <span>Saved {{ $bookmark->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>

                    {{-- Remove button --}}
                    <button wire:click="remove({{ $bookmark->post->id }})"
                            class="flex-shrink-0 self-center p-2 text-gray-400 hover:text-red-500 transition-colors"
                            title="Remove bookmark">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
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
