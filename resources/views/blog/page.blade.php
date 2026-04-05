<x-layouts.blog
    :title="$page->meta_title ?? $page->title"
    :description="$page->meta_description"
    :canonical="url($page->slug)">

    <article class="max-w-container mx-auto px-4 py-12">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-8">{{ $page->title }}</h1>

            <div class="prose prose-lg dark:prose-invert max-w-none
                         prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
                         prose-a:text-primary hover:prose-a:text-primary-hover
                         prose-img:rounded-lg">
                {!! $page->content !!}
            </div>
        </div>
    </article>

</x-layouts.blog>
