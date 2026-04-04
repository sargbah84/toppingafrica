<div>
    @if($this->history->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium">No reading history yet</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Articles you read will appear here.</p>
            <a href="{{ route('home') }}" class="inline-block mt-4 text-sm font-semibold text-primary hover:text-primary-hover transition-colors">
                Browse articles
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->history as $view)
                <a href="{{ route('blog.show', $view->post->slug) }}"
                   class="flex gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary/30 hover:shadow-sm transition group">
                    {{-- Thumbnail --}}
                    <div class="flex-shrink-0 w-24 h-16 sm:w-32 sm:h-20 rounded-md overflow-hidden bg-gray-100 dark:bg-gray-700">
                        @if($view->post->featured_image_url)
                            <img src="{{ $view->post->featured_image_url }}" alt="{{ $view->post->title }}"
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
                            {{ $view->post->title }}
                        </h3>
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                            @if($view->post->categories->isNotEmpty())
                                <span class="text-primary font-medium">{{ $view->post->categories->first()->name }}</span>
                                <span>&middot;</span>
                            @endif
                            <span>{{ $view->post->formatted_reading_time }}</span>
                            <span>&middot;</span>
                            <span>Read {{ $view->viewed_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </a>
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
