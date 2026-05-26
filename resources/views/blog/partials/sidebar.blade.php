{{-- Blog Post Sidebar --}}
<aside class="space-y-8">

    {{-- Ad: Sidebar --}}
    <x-ad-slot position="sidebar" />

    {{-- Top Creators --}}
    @if($topCreators->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg p-5 shadow-sm">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                Top Creators This Week
            </h3>
            <ul class="space-y-3">
                @foreach($topCreators as $index => $creator)
                    <li>
                        <a href="{{ route('creators.show', $creator->slug) }}" class="flex items-center gap-3 group">
                            <div class="relative flex-shrink-0">
                                <div class="w-12 h-12 rounded-full overflow-hidden">
                                    @if($creator->profile_image_url)
                                        <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}"
                                             class="w-full h-full object-cover" loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white text-xs font-bold"
                                             style="background-color: {{ $creator->avatar_color }}">
                                            {{ $creator->initials }}
                                        </div>
                                    @endif
                                </div>
                                <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-primary text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white dark:ring-gray-800">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-primary transition-colors line-clamp-1">
                                    {{ $creator->name }}
                                </span>
                                @php($followerShort = \App\Support\FollowerFormat::short($creator->follower_count))
                                <span class="block text-xs text-gray-400 dark:text-gray-500">
                                    @if($followerShort)
                                        {{ $followerShort }} followers
                                        @if($creator->category)
                                            · {{ $creator->category }}
                                        @endif
                                    @elseif($creator->category)
                                        {{ $creator->category }}
                                    @endif
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('creators.index') }}"
               class="mt-4 block w-full text-center text-sm font-semibold text-primary hover:text-primary-hover border border-primary hover:bg-primary hover:text-white rounded-lg py-2 transition-colors">
                View More Creators
            </a>
        </div>
    @endif

    {{-- Popular Posts --}}
    @if($popularPosts->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg p-5 shadow-sm">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                Popular Posts
            </h3>
            <ul class="space-y-4">
                @foreach($popularPosts as $index => $popular)
                    <li class="flex gap-3 items-start">
                        <span class="flex-shrink-0 w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-500 dark:text-gray-400">
                            {{ $index + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug">
                                <a href="{{ route('blog.show', $popular->slug) }}" class="hover:text-primary transition-colors line-clamp-2">
                                    {{ $popular->title }}
                                </a>
                            </h4>
                            @if($popular->categories->isNotEmpty())
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-primary">
                                    {{ $popular->categories->first()->name }}
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</aside>
