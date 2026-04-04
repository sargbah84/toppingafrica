<x-layouts.blog
    :title="$post->meta_title ?? $post->title"
    :metaDescription="$post->meta_description ?? $post->excerpt"
    ogType="article"
    :ogImage="$post->featured_image_url"
    :ogImageAlt="$post->title"
    :author="$post->author?->name"
    :publishedTime="($post->published_at ?? $post->created_at)->toIso8601String()"
    :modifiedTime="$post->updated_at->toIso8601String()"
    :section="$post->categories->isNotEmpty() ? $post->categories->first()->name : null"
    :tags="$post->tags->pluck('name')->toArray()"
    :canonical="route('blog.show', $post->slug)"
    :keywords="$post->focus_keyword ? [$post->focus_keyword, ...$post->tags->pluck('name')->toArray()] : $post->tags->pluck('name')->toArray()">

    @push('schema')
        <x-schema.json-ld page-type="article" :context="[
            'post' => $post,
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ...($post->categories->isNotEmpty() ? [['name' => $post->categories->first()->name, 'url' => route('blog.category', $post->categories->first()->slug)]] : []),
                ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
            ],
        ]" />
    @endpush

    <article class="max-w-container mx-auto px-4">

        {{-- Entry Header --}}
        <header class="max-w-3xl mx-auto pt-10 pb-8">
            <h1 class="text-3xl md:text-[2.8rem] font-black leading-tight text-gray-900 dark:text-white mb-6">
                {{ $post->title }}
            </h1>

            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                {{-- Author & Meta --}}
                <div class="flex items-center gap-4">
                    @if($post->author)
                        <img src="{{ $post->author->avatar_url }}" alt="{{ $post->author->name }}"
                             class="w-10 h-10 rounded-full object-cover">
                        <div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $post->author->name }}</span>
                            <div class="flex items-center gap-2 text-xs text-muted dark:text-gray-400">
                                <span>{{ ($post->published_at ?? $post->created_at)->format('M d, Y') }}</span>
                                <span>&middot;</span>
                                <span>{{ $post->formatted_reading_time }}</span>
                                <span>&middot;</span>
                                <span>{{ number_format($post->views_count) }} views</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Share Buttons --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 mr-1">Share:</span>
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($post->title) }}&url={{ urlencode(route('blog.show', $post->slug)) }}" target="_blank" rel="noopener"
                       class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:bg-primary hover:text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('blog.show', $post->slug)) }}" target="_blank" rel="noopener"
                       class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:bg-primary hover:text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Categories --}}
            @if($post->categories->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($post->categories as $cat)
                        <a href="{{ route('blog.category', $cat->slug) }}"
                           class="text-xs font-semibold uppercase tracking-wide text-primary hover:text-primary-hover transition-colors">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </header>

        {{-- Featured Image (hidden for interactive post types) --}}
        @if($post->featured_image_url && !in_array($post->post_type, ['video', 'poll', 'quiz', 'trivia']))
            <figure class="max-w-4xl mx-auto mb-10">
                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}"
                     class="w-full rounded-sm" loading="eager">
            </figure>
        @endif

        {{-- Content --}}
        <div class="max-w-3xl mx-auto mb-12">
            @if($post->excerpt)
                <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed mb-6 font-light italic">{{ $post->excerpt }}</p>
                <hr class="border-gray-200 dark:border-gray-700 mb-8">
            @endif

            <div class="prose prose-lg dark:prose-invert max-w-none
                        prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
                        prose-p:text-gray-700 dark:prose-p:text-gray-300 prose-p:leading-relaxed
                        prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                        prose-img:rounded-sm">
                {!! $post->content !!}
            </div>

            {{-- Type-specific interactive content --}}
            @if($post->post_type === 'quiz' && !empty($post->type_data['questions']))
                <div class="mt-8 mb-8">
                    <livewire:blog.quiz-player :post="$post" />
                </div>
            @elseif($post->post_type === 'trivia' && !empty($post->type_data['facts']))
                <div class="mt-8 mb-8">
                    <livewire:blog.trivia-cards :post="$post" />
                </div>
            @elseif($post->post_type === 'poll' && !empty($post->type_data['options']))
                <div class="mt-8 mb-8">
                    <livewire:blog.poll-widget :post="$post" />
                </div>
            @endif

            {{-- Ad: In Article --}}
            <x-ad-slot position="in_article" />

            {{-- Tags --}}
            @if($post->tags->isNotEmpty())
                <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 mr-2">Tags:</span>
                    <div class="inline-flex flex-wrap gap-2 mt-1">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('blog.tag', $tag->slug) }}"
                               class="inline-block px-3 py-1 text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-sm hover:bg-primary hover:text-white transition-colors">
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Author Box --}}
            @if($post->author)
                <div class="mt-10 p-6 bg-gray-50 dark:bg-gray-800/50 rounded-lg flex items-start gap-5">
                    <img src="{{ $post->author->avatar_url }}" alt="{{ $post->author->name }}"
                         class="w-16 h-16 rounded-full object-cover flex-shrink-0">
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-1">{{ $post->author->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $post->author->bio ?? 'Contributing writer at Topping Africa.' }}</p>
                    </div>
                </div>
            @endif

            {{-- Ad: After Content --}}
            <x-ad-slot position="after_content" />

            {{-- Comments --}}
            <div class="mt-12 mb-12">
                <livewire:blog.comments :postId="$post->id" />
            </div>
        </div>

        {{-- Related Posts --}}
        @if($relatedPosts->isNotEmpty())
            <section class="max-w-container mx-auto pb-16">
                <div class="border-t border-gray-200 dark:border-gray-700 pt-10">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Related Articles</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        @foreach($relatedPosts as $related)
                            @include('blog.partials.post-card', ['post' => $related])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

    </article>

</x-layouts.blog>
