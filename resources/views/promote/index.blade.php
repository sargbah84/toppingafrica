<x-layouts.blog
    :title="$page->meta_title ?: 'Promote with Topping Africa'"
    :metaDescription="$page->meta_description ?: 'Get your music, video, event or business featured on Topping Africa — published on our site, shared with our Facebook, Instagram and YouTube audience, and boosted for global reach.'"
    :canonical="url('/' . $page->slug)"
>

{{-- Hero: the page's own content (from the page editor) replaces the default copy when set. --}}
<section class="bg-gray-950 text-white">
    <div class="max-w-container mx-auto px-4 py-14 md:py-20">
        <div class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary mb-4">{{ $page->title }}</p>
            @if(trim(strip_tags((string) $page->content)) !== '')
                <div class="prose prose-lg prose-invert max-w-none mb-8 prose-headings:font-black prose-h1:text-3xl md:prose-h1:text-5xl prose-h1:leading-tight">
                    {!! $page->content !!}
                </div>
            @else
                <h1 class="text-3xl md:text-5xl font-black leading-tight mb-5">
                    Put your release in front of Africa &mdash; and the world.
                </h1>
                <p class="text-lg text-gray-300 leading-relaxed mb-8">
                    New song, music video, event, startup or product? Our editors turn it into a story, publish it on
                    Topping Africa, and share it with our community on Facebook, Instagram, YouTube and TikTok.
                </p>
            @endif
            <div class="flex flex-wrap gap-3">
                <a href="#packages" class="inline-flex items-center px-6 py-3 bg-primary text-white font-semibold rounded-md hover:bg-primary-hover transition-colors">
                    See packages
                </a>
                <a href="{{ url('/danny-attah-releases-vibrant-new-gospel-music-video-praise-partner') }}"
                   class="inline-flex items-center px-6 py-3 border border-gray-600 text-gray-200 font-semibold rounded-md hover:bg-white hover:text-gray-900 transition-colors">
                    See an example
                </a>
            </div>
        </div>
    </div>
</section>

@if(session('status'))
    <div class="max-w-container mx-auto px-4 pt-8">
        <div class="rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-700 dark:text-green-300">
            {{ session('status') }}
        </div>
    </div>
@endif

{{-- How it works --}}
<section class="max-w-container mx-auto px-4 py-12">
    <div class="grid gap-6 md:grid-cols-4">
        @foreach($steps as [$step, $heading, $copy])
            <div>
                <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-black flex items-center justify-center mb-3">{{ $step }}</div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">{{ $heading }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $copy }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- Packages --}}
<section id="packages" class="bg-gray-50 dark:bg-gray-900 border-y border-gray-200 dark:border-gray-800 scroll-mt-20">
    <div class="max-w-container mx-auto px-4 py-14">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mb-2">Packages</h2>
            <p class="text-gray-600 dark:text-gray-400">One-time price. No subscriptions. Every article stays on Topping Africa permanently.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($packages as $key => $package)
                <div @class([
                    'relative flex flex-col rounded-lg bg-white dark:bg-gray-800 p-6 border-2',
                    'border-primary shadow-lg' => !empty($package['popular']),
                    'border-gray-200 dark:border-gray-700' => empty($package['popular']),
                ])>
                    @if(!empty($package['popular']))
                        <span class="absolute -top-3 left-6 px-3 py-0.5 rounded-full bg-primary text-white text-xs font-bold uppercase tracking-wide">Most popular</span>
                    @endif
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">{{ $package['name'] }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4 min-h-[2.5rem]">{{ $package['tagline'] }}</p>
                    <p class="mb-1">
                        <span class="text-4xl font-black text-gray-900 dark:text-white">{{ \App\Models\PromotionRequest::formatMoney($package['price']) }}</span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">Delivered in {{ $package['turnaround'] }}</p>
                    <ul class="space-y-2 mb-6 flex-1">
                        @foreach($package['features'] as $feature)
                            <li class="flex gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('promote.checkout', ['package' => $key]) }}"
                       @class([
                           'block w-full py-2.5 rounded-md text-center text-sm font-semibold transition-colors',
                           'bg-primary text-white hover:bg-primary-hover' => !empty($package['popular']),
                           'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-200' => empty($package['popular']),
                       ])>
                        Choose {{ $package['name'] }}
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Add-ons --}}
        <div class="mt-12">
            <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1 text-center">Add-ons</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 text-center">Add any of these to your package at checkout.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 max-w-5xl mx-auto">
                @foreach($addons as $key => $addon)
                    <div class="flex items-start justify-between gap-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $addon['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $addon['description'] }}</p>
                        </div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white whitespace-nowrap">+{{ \App\Models\PromotionRequest::formatMoney($addon['price']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="text-center mt-10">
            <a href="{{ route('promote.checkout') }}" class="inline-flex items-center px-8 py-3 bg-primary text-white font-semibold rounded-md hover:bg-primary-hover transition-colors">
                Start your promotion &rarr;
            </a>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section>
    <div class="max-w-3xl mx-auto px-4 py-14">
        <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-6">Questions</h2>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($faqs as [$question, $answer])
                <details class="group py-4">
                    <summary class="flex justify-between items-center cursor-pointer font-semibold text-gray-900 dark:text-white">
                        {{ $question }}
                        <svg class="w-4 h-4 shrink-0 ml-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

</x-layouts.blog>
