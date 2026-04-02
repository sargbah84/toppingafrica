<x-layouts.blog title="Home" description="African News, Entertainment, Business, Technology, Sports & Culture - Your source for what's happening across Africa.">

    @push('schema')
        <x-schema.json-ld page-type="home" :context="[]" />
    @endpush

    {{-- Featured Post --}}
    @if($featured)
    <section class="max-w-container mx-auto px-4 pt-8 pb-4">
        <article class="relative group overflow-hidden rounded-sm">
            <a href="{{ route('blog.show', $featured->slug) }}">
                @if($featured->featured_image_url)
                    <img src="{{ $featured->featured_image_url }}" alt="{{ $featured->title }}"
                         class="w-full aspect-[21/9] object-cover transition-transform duration-700 group-hover:scale-105">
                @else
                    <div class="w-full aspect-[21/9] bg-gray-200 dark:bg-gray-800"></div>
                @endif
            </a>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10">
                @if($featured->categories->isNotEmpty())
                    <a href="{{ route('blog.category', $featured->categories->first()->slug) }}"
                       class="inline-block px-3 py-1 bg-primary text-white text-xs font-semibold uppercase tracking-wide rounded-sm mb-3">
                        {{ $featured->categories->first()->name }}
                    </a>
                @endif
                <h2 class="text-2xl md:text-4xl font-black text-white leading-tight mb-3">
                    <a href="{{ route('blog.show', $featured->slug) }}" class="hover:underline">{{ $featured->title }}</a>
                </h2>
                <div class="flex items-center gap-3 text-sm text-gray-300">
                    @if($featured->author)
                        <span>By <span class="text-white font-medium">{{ $featured->author->name }}</span></span>
                    @endif
                    <span>{{ ($featured->published_at ?? $featured->created_at)->format('M d, Y') }}</span>
                    <span>{{ $featured->formatted_reading_time }}</span>
                </div>
            </div>
        </article>
    </section>
    @endif

    {{-- Post Grid --}}
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($posts as $post)
                @include('blog.partials.post-card', ['post' => $post, 'showAuthor' => true])
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($posts->hasPages())
            <div class="mt-12">
                {{ $posts->links('blog.partials.pagination') }}
            </div>
        @endif
    </section>

</x-layouts.blog>
