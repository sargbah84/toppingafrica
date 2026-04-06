<x-layouts.blog
    :title="$page->meta_title ?? $page->title"
    :description="$page->meta_description ?? 'Browse ' . $page->title . ' articles on ' . \App\Models\Setting::get('site_name', config('app.name')) . '.'"
    :canonical="url($page->slug)">

    {{-- Header --}}
    <div class="bg-gray-50 dark:bg-gray-800/50 py-12">
        <div class="max-w-container mx-auto px-4">
            <div class="mb-2">
                <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">{{ $page->title }}</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-3">
                {{ $page->title }}
                <sup class="text-lg font-semibold text-gray-400 dark:text-gray-500">{{ $posts->total() }}</sup>
            </h1>
            @if ($page->content)
                <div class="text-gray-500 dark:text-gray-400 max-w-xl prose prose-sm dark:prose-invert">
                    {!! $page->content !!}
                </div>
            @endif
            <nav class="mt-4 text-sm text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-600 dark:text-gray-300">{{ $page->title }}</span>
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
                    <p class="text-gray-400 text-lg">No articles found yet.</p>
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
