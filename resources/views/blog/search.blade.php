<x-layouts.blog title="Search">

    <div class="bg-gray-50 dark:bg-gray-800/50 py-12">
        <div class="max-w-container mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-4">Search</h1>

            <form action="{{ route('blog.search') }}" method="GET" class="max-w-xl">
                <div class="relative">
                    <input type="text" name="q" value="{{ $query }}" placeholder="Search articles, creators..."
                           class="w-full pl-4 pr-12 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </button>
                </div>
            </form>

            @if($query)
                @php
                    $postCount = $posts instanceof \Illuminate\Pagination\LengthAwarePaginator ? $posts->total() : $posts->count();
                    $creatorCount = $creators->count();
                @endphp
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    {{ $postCount }} {{ \Illuminate\Support\Str::plural('article', $postCount) }}
                    and {{ $creatorCount }} {{ \Illuminate\Support\Str::plural('creator', $creatorCount) }}
                    for "<strong class="text-gray-700 dark:text-gray-200">{{ $query }}</strong>"
                </p>
            @endif
        </div>
    </div>

    <section class="max-w-container mx-auto px-4 py-10">
        @if($query)
            {{-- Creators --}}
            @if($creators->count())
                <div class="mb-12">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-5">Creators</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($creators as $creator)
                            <a href="{{ route('creators.show', $creator->slug) }}"
                               class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-primary transition-colors text-center">
                                <div class="mx-auto w-16 h-16 rounded-full overflow-hidden">
                                    @if($creator->profile_image_url)
                                        <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold"
                                             style="background-color: {{ $creator->avatar_color }}">
                                            {{ $creator->initials }}
                                        </div>
                                    @endif
                                </div>
                                <h3 class="mt-3 text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors truncate">
                                    {{ $creator->name }}
                                </h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $creator->category }}{{ $creator->country ? ' · ' . $creator->country : '' }}
                                </p>
                                @php($followerShort = \App\Support\FollowerFormat::short($creator->follower_count))
                                @if($followerShort)
                                    <p class="mt-0.5 text-[11px] font-semibold text-primary">{{ $followerShort }} followers</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Articles --}}
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-5">Articles</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @forelse($posts as $post)
                    @include('blog.partials.post-card', ['post' => $post, 'showAuthor' => true])
                @empty
                    <div class="col-span-full text-center py-16">
                        <p class="text-gray-400 text-lg">No articles found matching your search.</p>
                    </div>
                @endforelse
            </div>

            @if($posts instanceof \Illuminate\Pagination\LengthAwarePaginator && $posts->hasPages())
                <div class="mt-12">
                    {{ $posts->links('blog.partials.pagination') }}
                </div>
            @endif
        @else
            <div class="text-center py-16">
                <p class="text-gray-400 text-lg">Enter a search term to find articles and creators.</p>
            </div>
        @endif
    </section>

</x-layouts.blog>
