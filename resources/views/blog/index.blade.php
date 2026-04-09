<x-layouts.blog title="Home" :description="\App\Models\Setting::get('site_description')">

    @push('schema')
        <x-schema.json-ld page-type="home" :context="[]" />
    @endpush

    {{-- Section 1: Hero - 3 Featured Posts --}}
    @if($heroPost->isNotEmpty())
    <section class="max-w-container mx-auto px-4 pt-8 pb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($heroPost as $post)
            <article class="relative group overflow-hidden rounded-sm">
                <a href="{{ route('blog.show', $post->slug) }}">
                    @if($post->featured_image_url)
                        <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                             class="w-full aspect-[16/9] object-cover transition-transform duration-700 group-hover:scale-105">
                    @else
                        <div class="w-full aspect-[16/9] bg-gray-200 dark:bg-gray-800"></div>
                    @endif
                </a>
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-5">
                    @if($post->categories->isNotEmpty())
                        <a href="{{ route('blog.category', $post->categories->first()->slug) }}"
                           class="inline-block px-2.5 py-1 bg-primary text-white text-[10px] font-semibold uppercase tracking-wide rounded-sm mb-2">
                            {{ $post->categories->first()->name }}
                        </a>
                    @endif
                    <h2 class="text-lg font-bold text-white leading-snug mb-2 line-clamp-2">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                    </h2>
                    <div class="flex items-center gap-2 text-xs text-gray-300">
                        @if($post->author)
                            <span>By <span class="text-white font-medium">{{ $post->author->name }}</span></span>
                        @endif
                        <span>{{ ($post->published_at ?? $post->created_at)->format('M d, Y') }}</span>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Section 2: Most Popular --}}
    @if($mostPopular->isNotEmpty())
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="mb-6">
            <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
            <span class="text-xs font-semibold uppercase tracking-wider text-primary">Trending</span>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white mt-1">Most Popular</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach($mostPopular as $post)
            <article class="group">
                <figure class="relative mb-4 overflow-hidden rounded-sm">
                    <a href="{{ route('blog.show', $post->slug) }}">
                        @if($post->featured_image_url)
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                                 class="w-full aspect-[16/9] object-cover transition-transform duration-500 group-hover:scale-105"
                                 loading="lazy">
                        @else
                            <div class="w-full aspect-[16/9] bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                            </div>
                        @endif
                    </a>
                    {{-- Date Badge (rotated, Athena style) --}}
                    <div class="absolute top-0 left-0 bg-white dark:bg-gray-800 px-2 py-3 text-center shadow-sm">
                        <span class="block text-xs font-bold text-gray-900 dark:text-white uppercase" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                            {{ ($post->published_at ?? $post->created_at)->format('M d') }}
                        </span>
                    </div>
                </figure>
                <div class="flex items-center gap-2 mb-2 text-xs font-semibold uppercase tracking-wide">
                    @if($post->categories->isNotEmpty())
                        @foreach($post->categories->take(2) as $cat)
                            <a href="{{ route('blog.category', $cat->slug) }}"
                               class="text-primary hover:text-primary-hover transition-colors">
                                {{ $cat->name }}
                            </a>
                            @if(!$loop->last)
                                <span class="text-gray-400">&#8226;</span>
                            @endif
                        @endforeach
                    @endif
                </div>
                <h3 class="text-xl font-bold leading-snug text-gray-900 dark:text-white">
                    <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary dark:hover:text-primary transition-colors line-clamp-2">
                        {{ $post->title }}
                    </a>
                </h3>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Section 3: Trending Articles Grid --}}
    @if($trending->isNotEmpty())
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($trending as $post)
                @include('blog.partials.post-card', ['post' => $post])
            @endforeach
        </div>
        <div class="text-center mt-8">
            <a href="#" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline transition-colors">
                See all trending articles
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </section>
    @endif

    {{-- Section: Creators carousel (8 per page, dot navigation) --}}
    @if($creators->isNotEmpty())
    @php $creatorPages = $creators->chunk(8); @endphp
    <section class="max-w-container mx-auto px-4 py-10"
             x-data="{ page: 0, total: {{ $creatorPages->count() }} }">
        <div class="flex items-end justify-between mb-6">
            <div>
                <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">People to Watch</span>
                <h2 class="text-3xl font-black text-gray-900 dark:text-white mt-1">Creators</h2>
            </div>
            <a href="{{ template_url('creators') }}"
               class="hidden md:inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-200 rounded-sm hover:bg-gray-900 hover:text-white hover:border-gray-900 dark:hover:bg-white dark:hover:text-gray-900 transition-colors">
                See All
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>

        {{-- Pages viewport --}}
        <div class="overflow-hidden py-2">
            <div class="flex transition-transform duration-500 ease-out"
                 :style="`transform: translateX(-${page * 100}%)`">
                @foreach($creatorPages as $pageIndex => $pageCreators)
                    <div class="w-full flex-shrink-0 grid grid-cols-4 sm:grid-cols-8 gap-4">
                        @foreach($pageCreators as $creator)
                            <a href="{{ $creator->public_url }}" class="group text-center rounded-xl p-2 hover:shadow-xl transition-all duration-300">
                                <div class="relative w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-full overflow-hidden ring-2 ring-transparent group-hover:ring-primary transition">
                                    @if($creator->profile_image_url)
                                        <img src="{{ $creator->profile_image_url }}"
                                             alt="{{ $creator->name }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold"
                                             style="background-color: {{ $creator->avatar_color }}">
                                            {{ $creator->initials }}
                                        </div>
                                    @endif
                                </div>

                                @if($creator->is_featured || $creator->is_trending || $creator->is_rising)
                                    <div class="mt-1 flex justify-center">
                                        @if($creator->is_featured)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-primary text-white text-[10px] font-bold uppercase tracking-wide">
                                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 0 0 .95.69h4.16c.969 0 1.371 1.24.588 1.81l-3.366 2.446a1 1 0 0 0-.364 1.118l1.286 3.957c.3.922-.755 1.688-1.54 1.118l-3.366-2.446a1 1 0 0 0-1.176 0l-3.366 2.446c-.785.57-1.84-.196-1.54-1.118l1.286-3.957a1 1 0 0 0-.364-1.118L2.05 9.384c-.783-.57-.38-1.81.588-1.81h4.16a1 1 0 0 0 .95-.69l1.286-3.957Z"/></svg>
                                                Featured
                                            </span>
                                        @elseif($creator->is_trending)
                                            <span class="inline-flex px-1.5 py-0.5 rounded-full bg-pink-500 text-white text-[10px] font-bold uppercase">Hot</span>
                                        @elseif($creator->is_rising)
                                            <span class="inline-flex px-1.5 py-0.5 rounded-full bg-orange-500 text-white text-[10px] font-bold uppercase">New</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white truncate" title="{{ $creator->name }}">
                                    {{ $creator->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $creator->category }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Dots --}}
        @if($creatorPages->count() > 1)
            <div class="mt-6 flex justify-center items-center gap-2">
                <template x-for="i in total" :key="i">
                    <button type="button"
                            @click="page = i - 1"
                            :aria-label="`Go to page ${i}`"
                            :class="page === i - 1
                                ? 'w-6 h-2 bg-primary'
                                : 'w-2 h-2 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500'"
                            class="rounded-full transition-all duration-300"></button>
                </template>
            </div>
        @endif
    </section>
    @endif

    {{-- Section 4: Featured (Music Videos of the Week) --}}
    @if($featuredVideos->isNotEmpty())
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="mb-6">
            <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
            <span class="text-xs font-semibold uppercase tracking-wider text-primary">Music Video of the Week</span>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white mt-1">Featured</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($featuredVideos as $post)
                @include('blog.partials.post-card', ['post' => $post])
            @endforeach
        </div>
    </section>
    @endif

    {{-- Section 5: Explore What's On TV (Dark Section) --}}
    @if($tvPosts->isNotEmpty())
    <section class="bg-gray-900 py-12">
        <div class="max-w-container mx-auto px-4">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-primary">Movies + TV</span>
                    <h2 class="text-3xl font-black text-white mt-1">Explore What's On TV</h2>
                </div>
                <a href="{{ route('blog.category', 'movies-tv') }}"
                   class="hidden md:inline-flex items-center gap-2 px-5 py-2.5 border border-gray-600 text-sm font-semibold text-white rounded-sm hover:bg-white hover:text-gray-900 transition-colors">
                    See More
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Left: Large Card --}}
                @if($tvPosts->first())
                @php $mainTv = $tvPosts->first(); @endphp
                <article class="group">
                    <figure class="relative mb-4 overflow-hidden rounded-sm">
                        <a href="{{ route('blog.show', $mainTv->slug) }}">
                            @if($mainTv->featured_image_url)
                                <img src="{{ $mainTv->featured_image_url }}" alt="{{ $mainTv->title }}"
                                     class="w-full aspect-[16/9] object-cover transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">
                            @else
                                <div class="w-full aspect-[16/9] bg-gray-800 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                </div>
                            @endif
                        </a>
                    </figure>
                    <div class="flex items-center gap-2 mb-2 text-xs font-semibold uppercase tracking-wide">
                        @if($mainTv->categories->isNotEmpty())
                            <a href="{{ route('blog.category', $mainTv->categories->first()->slug) }}"
                               class="text-primary hover:text-primary-hover transition-colors">
                                {{ $mainTv->categories->first()->name }}
                            </a>
                        @endif
                    </div>
                    @if($mainTv->author)
                        <p class="text-xs text-gray-400 mb-1">By {{ $mainTv->author->name }}</p>
                    @endif
                    <h3 class="text-xl font-bold leading-snug text-white mb-2">
                        <a href="{{ route('blog.show', $mainTv->slug) }}" class="hover:text-primary transition-colors line-clamp-2">
                            {{ $mainTv->title }}
                        </a>
                    </h3>
                    @if($mainTv->excerpt)
                        <p class="text-sm text-gray-400 line-clamp-2">{{ $mainTv->excerpt }}</p>
                    @endif
                </article>
                @endif

                {{-- Right: Stacked Small Cards --}}
                <div class="space-y-6">
                    @foreach($tvPosts->skip(1) as $post)
                    <article class="group flex gap-4">
                        <figure class="flex-shrink-0 w-28 h-20 overflow-hidden rounded-sm">
                            <a href="{{ route('blog.show', $post->slug) }}">
                                @if($post->featured_image_url)
                                    <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full bg-gray-800 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                    </div>
                                @endif
                            </a>
                        </figure>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 text-xs font-semibold uppercase tracking-wide">
                                @if($post->categories->isNotEmpty())
                                    <a href="{{ route('blog.category', $post->categories->first()->slug) }}"
                                       class="text-primary hover:text-primary-hover transition-colors">
                                        {{ $post->categories->first()->name }}
                                    </a>
                                @endif
                            </div>
                            <h4 class="text-sm font-bold leading-snug text-white line-clamp-2">
                                <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary transition-colors">
                                    {{ $post->title }}
                                </a>
                            </h4>
                            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                <span>{{ ($post->published_at ?? $post->created_at)->format('M d, Y') }}</span>
                                <span>{{ $post->formatted_reading_time }}</span>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
            {{-- Mobile See More --}}
            <div class="mt-8 text-center md:hidden">
                <a href="{{ route('blog.category', 'movies-tv') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-600 text-sm font-semibold text-white rounded-sm hover:bg-white hover:text-gray-900 transition-colors">
                    See More
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- Section 6: Latest Stories + Editor's Picked --}}
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
            {{-- Left: Latest Stories (3/5 width) --}}
            <div class="lg:col-span-3">
                <div class="mb-6">
                    <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-primary">Recent</span>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mt-1">Latest Stories</h2>
                </div>
                <div class="space-y-6">
                    @foreach($latestStories as $post)
                    <article class="group pb-6 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}" style="display: flex; gap: 1.25rem;">
                        <a href="{{ route('blog.show', $post->slug) }}" class="block flex-shrink-0" style="width: 200px; height: 140px;">
                            @if($post->featured_image_url)
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                                     style="width: 200px; height: 140px; object-fit: cover; border-radius: 2px;"
                                     class="transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">
                            @else
                                <div style="width: 200px; height: 140px; border-radius: 2px;" class="bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                </div>
                            @endif
                        </a>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 text-xs font-semibold uppercase tracking-wide">
                                @if($post->categories->isNotEmpty())
                                    <a href="{{ route('blog.category', $post->categories->first()->slug) }}"
                                       class="text-primary hover:text-primary-hover transition-colors">
                                        {{ $post->categories->first()->name }}
                                    </a>
                                @endif
                                @if($post->author)
                                    <span class="text-gray-400">&#8226;</span>
                                    <span class="text-gray-500 dark:text-gray-400">By {{ $post->author->name }}</span>
                                @endif
                            </div>
                            <h3 class="text-base md:text-lg font-bold leading-snug text-gray-900 dark:text-white mb-1">
                                <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary dark:hover:text-primary transition-colors line-clamp-2">
                                    {{ $post->title }}
                                </a>
                            </h3>
                            @if($post->excerpt)
                                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 hidden md:block">{{ $post->excerpt }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                <span>{{ ($post->published_at ?? $post->created_at)->format('M d, Y') }}</span>
                                <span>{{ $post->formatted_reading_time }}</span>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>

            {{-- Right: Editor's Picked (2/5 width) --}}
            <div class="lg:col-span-2">
                <div class="mb-6">
                    <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-primary">Curated</span>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mt-1">Editor's Picked</h2>
                </div>
                <div class="space-y-5">
                    @foreach($editorsPicked as $post)
                    <article class="group flex gap-4 pb-5 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                        <figure class="flex-shrink-0 w-20 h-20 overflow-hidden rounded-sm">
                            <a href="{{ route('blog.show', $post->slug) }}">
                                @if($post->featured_image_url)
                                    <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                    </div>
                                @endif
                            </a>
                        </figure>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 text-xs font-semibold uppercase tracking-wide">
                                @if($post->categories->isNotEmpty())
                                    <a href="{{ route('blog.category', $post->categories->first()->slug) }}"
                                       class="text-primary hover:text-primary-hover transition-colors">
                                        {{ $post->categories->first()->name }}
                                    </a>
                                @endif
                            </div>
                            <h4 class="text-sm font-bold leading-snug text-gray-900 dark:text-white line-clamp-2">
                                <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary dark:hover:text-primary transition-colors">
                                    {{ $post->title }}
                                </a>
                            </h4>
                            <span class="text-xs text-gray-400 mt-1 block">{{ ($post->published_at ?? $post->created_at)->format('M d, Y') }}</span>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

</x-layouts.blog>
