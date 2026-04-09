@php
    $page = $page ?? null;
    $pageTitle = $page?->meta_title ?: ($page?->title ?? 'African Content Creators');
    $pageDescription = $page?->meta_description ?: 'Discover rising and trending African content creators across comedy, fashion, food, music, beauty and more.';
    $heroTitle = $page?->title ?? 'African Content Creators';
@endphp

<x-layouts.blog
    :title="$pageTitle"
    :metaDescription="$pageDescription"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ $pageTitle }}",
    "description": "{{ $pageDescription }}",
    "url": "{{ url()->current() }}"
}
</script>
@endpush

<section class="max-w-container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-2">{{ $heroTitle }}</h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-none mx-auto">
            {{ $pageDescription }}
        </p>
    </div>

    @if (! empty($page?->content))
        <div class="prose dark:prose-invert max-w-3xl mx-auto mb-8">
            {!! $page->content !!}
        </div>
    @endif

    {{-- Search with auto-suggestions --}}
    <div class="mb-6">
        <div class="max-w-xl mx-auto"
             x-data="{
                 query: '{{ request('search') }}',
                 results: [],
                 open: false,
                 active: -1,
                 timer: null,
                 async search() {
                     if (this.query.length < 2) { this.results = []; this.open = false; return; }
                     const res = await fetch('{{ route('creators.suggest') }}?q=' + encodeURIComponent(this.query));
                     this.results = await res.json();
                     this.open = this.results.length > 0;
                     this.active = -1;
                 },
                 debounce() {
                     clearTimeout(this.timer);
                     this.timer = setTimeout(() => this.search(), 250);
                 },
                 go(url) { window.location.href = url; },
                 onKeydown(e) {
                     if (!this.open) return;
                     if (e.key === 'ArrowDown') { e.preventDefault(); this.active = Math.min(this.active + 1, this.results.length - 1); }
                     else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = Math.max(this.active - 1, 0); }
                     else if (e.key === 'Enter' && this.active >= 0) { e.preventDefault(); this.go(this.results[this.active].url); }
                 }
             }"
             @click.outside="open = false">
            <form action="{{ route('creators.index') }}" method="GET">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('country'))
                    <input type="hidden" name="country" value="{{ request('country') }}">
                @endif
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" name="search" x-model="query" @input="debounce()" @keydown="onKeydown" @focus="if (results.length) open = true"
                           autocomplete="off" placeholder="Search creators by name, category or country..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-base text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary focus:ring-1 focus:ring-primary shadow-sm">

                    {{-- Suggestions dropdown --}}
                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden z-30">
                        <template x-for="(item, i) in results" :key="item.slug">
                            <a :href="item.url" @mouseenter="active = i"
                               :class="active === i ? 'bg-gray-50 dark:bg-gray-700/50' : ''"
                               class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer">
                                <template x-if="item.image">
                                    <img :src="item.image" :alt="item.name" class="w-9 h-9 rounded-full object-cover flex-shrink-0">
                                </template>
                                <template x-if="!item.image">
                                    <div class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400" x-text="item.initials"></span>
                                    </div>
                                </template>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="item.name"></div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                        <span x-text="item.category"></span> <span class="mx-0.5">&middot;</span> <span x-text="item.country"></span>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        </template>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-8">
        {{-- Category Pills --}}
        @php
            $visibleCategories = $categories->take(5);
            $otherCategories = $categories->slice(5);
            $activeCategory = request('category');
            $activeIsInOthers = $activeCategory && $otherCategories->contains($activeCategory);
        @endphp
        <div class="flex flex-wrap gap-2 items-center">
            <a href="{{ template_url('creators') }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ !$activeCategory ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                All
            </a>
            @foreach($visibleCategories as $cat)
                <a href="{{ route('creators.index', ['category' => $cat, 'country' => request('country')]) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $activeCategory === $cat ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $cat }}
                </a>
            @endforeach

            @if($otherCategories->isNotEmpty())
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $activeIsInOthers ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                        {{ $activeIsInOthers ? 'Others: ' . $activeCategory : 'Others' }}
                        <svg class="w-3 h-3" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 20 20" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute left-0 mt-2 w-44 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-20 py-1 max-h-72 overflow-y-auto">
                        @foreach($otherCategories as $cat)
                            <a href="{{ route('creators.index', ['category' => $cat, 'country' => request('country')]) }}"
                               class="block px-4 py-2 text-xs font-semibold transition {{ $activeCategory === $cat ? 'bg-primary text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                {{ $cat }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Country Filter --}}
        @if($countries->count() > 1)
            <div class="sm:ml-auto">
                <select onchange="window.location.href=this.value"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <option value="{{ route('creators.index', ['category' => request('category')]) }}" {{ !request('country') ? 'selected' : '' }}>All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ route('creators.index', ['category' => request('category'), 'country' => $country]) }}" {{ request('country') === $country ? 'selected' : '' }}>
                            {{ $country }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- Creator Grid --}}
    @if($creators->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($creators as $creator)
                <div class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 pt-2">
                    {{-- Follow heart (top-left) --}}
                    <livewire:creator-follow-button :creator="$creator" variant="heart" :key="'follow-list-'.$creator->id" />

                    {{-- Status badge (top-right) --}}
                    <div class="absolute top-3 right-3 z-10 flex items-center gap-1">
                        @if($creator->is_featured)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 0 0 .95.69h4.16c.969 0 1.371 1.24.588 1.81l-3.366 2.446a1 1 0 0 0-.364 1.118l1.286 3.957c.3.922-.755 1.688-1.54 1.118l-3.366-2.446a1 1 0 0 0-1.176 0l-3.366 2.446c-.785.57-1.84-.196-1.54-1.118l1.286-3.957a1 1 0 0 0-.364-1.118L2.05 9.384c-.783-.57-.38-1.81.588-1.81h4.16a1 1 0 0 0 .95-.69l1.286-3.957Z"/></svg>
                                Featured
                            </span>
                        @elseif($creator->is_trending)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400">Trending</span>
                        @elseif($creator->is_rising)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">Rising</span>
                        @endif
                    </div>

                    {{-- Avatar --}}
                    <a href="{{ route('creators.show', $creator->slug) }}" class="block">
                        <div class="mt-10 mx-auto w-20 h-20 rounded-full overflow-hidden">
                            @if($creator->profile_image_url)
                                <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-white text-xl font-bold"
                                     style="background-color: {{ $creator->avatar_color }}">
                                    {{ $creator->initials }}
                                </div>
                            @endif
                        </div>

                        <div class="px-5 pt-3 pb-5 text-center">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors truncate">
                                {{ $creator->name }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $creator->category }} &middot; {{ $creator->country }}
                            </p>
                            @php($followerShort = \App\Support\FollowerFormat::short($creator->follower_count))
                            @if($followerShort)
                                <p class="mt-0.5 text-[11px] font-semibold text-primary">
                                    {{ $followerShort }} followers
                                    @if($creator->follower_platform)
                                        · {{ ucfirst($creator->follower_platform) }}
                                    @endif
                                </p>
                            @endif

                            {{-- Social icons row --}}
                            @if($creator->socialLinks->count())
                                <div class="flex items-center justify-center gap-3 mt-3 text-gray-400 dark:text-gray-500">
                                    @foreach($creator->socialLinks->take(4) as $link)
                                        @include('creators.partials.social-icon', ['platform' => $link->platform, 'size' => 'w-4 h-4'])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($creators->hasPages())
            <div class="mt-8">
                {{ $creators->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <p class="text-gray-500 dark:text-gray-400">No creators found matching your filters.</p>
        </div>
    @endif
</section>

</x-layouts.blog>
