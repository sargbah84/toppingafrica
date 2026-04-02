<x-layouts.blog
    :title="$tag->name"
    :description="'Articles tagged with ' . $tag->name . ' on Topping Africa.'"
    :canonical="route('blog.tag', $tag->slug)">

    @push('schema')
        <x-schema.json-ld page-type="tag" :context="[
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => $tag->name, 'url' => route('blog.tag', $tag->slug)],
            ],
        ]" />
    @endpush

    <div class="bg-gray-50 dark:bg-gray-800/50 py-12">
        <div class="max-w-container mx-auto px-4">
            <div class="mb-2">
                <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">Tag</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-3">#{{ $tag->name }}</h1>
            <nav class="mt-4 text-sm text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-600 dark:text-gray-300">{{ $tag->name }}</span>
            </nav>
        </div>
    </div>

    <section class="max-w-container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse($posts as $post)
                @include('blog.partials.post-card', ['post' => $post, 'showAuthor' => true])
            @empty
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-400 text-lg">No articles found with this tag.</p>
                </div>
            @endforelse
        </div>

        @if($posts->hasPages())
            <div class="mt-12">
                {{ $posts->links('blog.partials.pagination') }}
            </div>
        @endif
    </section>

</x-layouts.blog>
