<x-layouts.blog
    :title="$category->name"
    :description="$category->description ?? 'Browse ' . $category->name . ' articles on Topping Africa.'"
    :canonical="route('blog.category', $category->slug)">

    @push('schema')
        <x-schema.json-ld page-type="category" :context="[
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => $category->name, 'url' => route('blog.category', $category->slug)],
            ],
        ]" />
    @endpush

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 dark:bg-gray-800/50 py-12">
        <div class="max-w-container mx-auto px-4">
            <div class="mb-2">
                <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">Category</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-3">{{ $category->name }} <sup class="text-lg font-semibold text-gray-400 dark:text-gray-500">{{ $category->posts_count ?? $posts->total() }}</sup></h1>
            @if($category->description)
                <p class="text-gray-500 dark:text-gray-400 max-w-xl">{{ $category->description }}</p>
            @endif
            <nav class="mt-4 text-sm text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-600 dark:text-gray-300">{{ $category->name }}</span>
            </nav>
        </div>
    </div>

    {{-- Posts Grid --}}
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse($posts as $post)
                @include('blog.partials.post-card', ['post' => $post, 'showAuthor' => true])
            @empty
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-400 text-lg">No articles found in this category.</p>
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
