<x-layouts.blog
    title="African Content Creators"
    :metaDescription="'Discover rising and trending African content creators across comedy, fashion, food, music and more.'"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "African Content Creators — Topping Africa",
    "description": "Discover rising and trending African content creators across comedy, fashion, food, music and more.",
    "url": "{{ url('/creators') }}"
}
</script>
@endpush

<section class="max-w-container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-2">African Content Creators</h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            Discover rising and trending African content creators across comedy, fashion, food, music and more.
        </p>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-8">
        {{-- Category Pills --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('creators.index') }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ !request('category') ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                All
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('creators.index', ['category' => $cat, 'country' => request('country')]) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ request('category') === $cat ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $cat }}
                </a>
            @endforeach
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
                <div class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
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
