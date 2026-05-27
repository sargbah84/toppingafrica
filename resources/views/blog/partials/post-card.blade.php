{{-- Post Card Grid Style (Athena-inspired).
     Pass $aspectAuto = true to let images flow at natural height (e.g. for masonry layouts). --}}
@php
    $imageClasses = (isset($aspectAuto) && $aspectAuto)
        ? 'w-full h-auto object-cover object-top transition-transform duration-500 group-hover:scale-105'
        : 'w-full aspect-[4/3] object-cover object-top transition-transform duration-500 group-hover:scale-105';
    $placeholderClasses = (isset($aspectAuto) && $aspectAuto)
        ? 'w-full aspect-[4/3] bg-gray-100 dark:bg-gray-800 flex items-center justify-center'
        : 'w-full aspect-[4/3] bg-gray-100 dark:bg-gray-800 flex items-center justify-center';
@endphp
<article class="group animate-fade-in">
    {{-- Image --}}
    <figure class="relative mb-5 overflow-hidden rounded-sm">
        <a href="{{ route('blog.show', $post->slug) }}">
            @if($post->featured_image_url)
                <img src="{{ $post->featured_image_url }}"
                     alt="{{ $post->title }}"
                     class="{{ $imageClasses }}"
                     loading="lazy">
            @else
                <div class="{{ $placeholderClasses }}">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                </div>
            @endif
        </a>

        {{-- Date Badge (rotated, Athena style) --}}
        @if(!isset($hideDate) || !$hideDate)
        <div class="absolute top-0 left-0 bg-white dark:bg-gray-800 px-2 py-3 text-center shadow-sm">
            <span class="block text-xs font-bold text-gray-900 dark:text-white uppercase" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                {{ ($post->published_at ?? $post->created_at)->format('M d') }}
            </span>
        </div>
        @endif
    </figure>

    {{-- Meta --}}
    <div class="flex items-center gap-2 mb-2 text-xs font-semibold uppercase tracking-wide">
        @if($post->categories->isNotEmpty())
            <a href="{{ route('blog.category', $post->categories->first()->slug) }}"
               class="text-primary hover:text-primary-hover transition-colors">
                {{ $post->categories->first()->name }}
            </a>
        @endif
        @if(isset($showAuthor) && $showAuthor && $post->author)
            <span class="text-gray-400">&#8226;</span>
            <span class="text-muted dark:text-gray-400">By {{ $post->author->display_name }}</span>
        @endif
    </div>

    {{-- Title --}}
    <h3 class="text-lg font-bold leading-snug text-gray-900 dark:text-white pr-4">
        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-primary dark:hover:text-primary transition-colors line-clamp-2">
            {{ $post->title }}
        </a>
    </h3>

    {{-- Engagement Counts --}}
    @if(($post->reactions_count ?? 0) > 0 || ($post->comments_count ?? 0) > 0)
        <div class="mt-2 flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
            @if(($post->reactions_count ?? 0) > 0)
                <span title="{{ $post->reactions_count }} reactions">{{ config('blog.reactions.fire.emoji', "\u{1F525}") }} {{ $post->reactions_count }}</span>
            @endif
            @if(($post->comments_count ?? 0) > 0)
                <span title="{{ $post->comments_count }} comments">{{ "\u{1F4AC}" }} {{ $post->comments_count }}</span>
            @endif
        </div>
    @endif
</article>
