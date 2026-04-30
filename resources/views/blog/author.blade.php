<x-layouts.blog
    :title="$author->name . ' - Author'"
    :description="$author->bio ?? $author->name . ' is a contributing writer at Topping Africa.'"
    :canonical="route('blog.author', $author->username)">

    @push('schema')
        <x-schema.json-ld page-type="author" :context="[
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => $author->name, 'url' => route('blog.author', $author->username)],
            ],
        ]" />
    @endpush

    {{-- Author Header --}}
    <div class="bg-gray-50 dark:bg-gray-800/50 py-12">
        <div class="max-w-container mx-auto px-4">
            <div class="flex flex-col sm:flex-row items-start gap-6">
                <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}"
                     class="w-24 h-24 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div>
                    <div class="mb-2">
                        <span class="inline-block w-8 h-[3px] bg-primary mr-2 align-middle"></span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary">Author</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white mb-2">{{ $author->name }}</h1>
                    @if($author->bio)
                        <p class="text-gray-500 dark:text-gray-400 max-w-xl leading-relaxed">{{ $author->bio }}</p>
                    @endif

                    @if($author->social_links)
                        <div class="flex items-center gap-3 mt-4">
                            @if($author->social_links['twitter'] ?? null)
                                <a href="{{ $author->social_links['twitter'] }}" target="_blank" rel="noopener"
                                   class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                                </a>
                            @endif
                            @if($author->social_links['facebook'] ?? null)
                                <a href="{{ $author->social_links['facebook'] }}" target="_blank" rel="noopener"
                                   class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                                </a>
                            @endif
                            @if($author->social_links['linkedin'] ?? null)
                                <a href="{{ $author->social_links['linkedin'] }}" target="_blank" rel="noopener"
                                   class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                            @endif
                            @if($author->website)
                                <a href="{{ $author->website }}" target="_blank" rel="noopener"
                                   class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-primary hover:text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.732-3.559"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center gap-4 mt-4 text-sm text-gray-400 dark:text-gray-500">
                        <span>{{ $posts->total() }} {{ Str::plural('article', $posts->total()) }}</span>
                    </div>

                    <nav class="mt-4 text-sm text-gray-400">
                        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-600 dark:text-gray-300">{{ $author->name }}</span>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Posts Grid --}}
    <section class="max-w-container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse($posts as $post)
                @include('blog.partials.post-card', ['post' => $post, 'showAuthor' => false])
            @empty
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-400 text-lg">No articles by this author yet.</p>
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
