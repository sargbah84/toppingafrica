<x-layouts.blog
    :title="$page->meta_title ?: ($page->title . ' — ' . config('app.name'))"
    :metaDescription="$page->meta_description ?: 'Discover what\'s trending across Africa right now.'"
>
    @if (! empty($page->content))
        <section class="max-w-container mx-auto px-4 pt-8">
            <div class="prose dark:prose-invert max-w-none">
                {!! $page->content !!}
            </div>
        </section>
    @endif

    <livewire:trending-page />
</x-layouts.blog>
