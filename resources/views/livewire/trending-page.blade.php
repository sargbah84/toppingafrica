<div class="max-w-container mx-auto px-4 py-12">
    @php
        $categoryColors = [
            'Music' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',
            'Business' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'Sports' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
            'Culture' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'Tech' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            'Lifestyle' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        ];
        $filtered = $this->filteredTrends;
    @endphp

    {{-- Header --}}
    <div class="mb-10 text-center max-w-3xl mx-auto">
        <h1 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white tracking-tight">
            Trending Across Africa Today
        </h1>
        <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">
            Positive vibes, viral moments, and big moves across the continent
        </p>

        @if($lastUpdated)
            <div class="mt-5 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-xs font-semibold text-green-700 dark:text-green-300">
                    @if($lastUpdated->isToday())
                        Updated today
                    @else
                        Last updated {{ $lastUpdated->diffForHumans() }}
                    @endif
                </span>
            </div>
        @endif
    </div>

    {{-- Category filter tabs --}}
    <div class="flex flex-wrap items-center justify-center gap-2 mb-10" wire:loading.class="opacity-60">
        @foreach($categories as $category)
            <button
                type="button"
                wire:click="filterByCategory('{{ $category }}')"
                wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-semibold rounded-full border transition-colors
                    {{ $activeCategory === $category
                        ? 'bg-primary text-white border-primary'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-primary hover:text-primary' }}">
                {{ $category }}
            </button>
        @endforeach
    </div>

    {{-- Trend grid --}}
    @if($filtered->isEmpty())
        <div class="text-center py-20 max-w-lg mx-auto">
            <div class="text-6xl mb-4">🌍</div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                We're gathering today's trends
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Check back soon for the latest positive vibes from across Africa.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" wire:loading.class="opacity-60">
            @foreach($filtered as $trend)
                <article class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                    {{-- Top row: country + category --}}
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            @if($flag = $trend->flagEmoji())
                                <span class="text-lg leading-none">{{ $flag }}</span>
                            @endif
                            <span>{{ $trend->country }}</span>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-full {{ $categoryColors[$trend->category] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $trend->category }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h2 class="text-xl font-black text-gray-900 dark:text-white leading-snug mb-3">
                        {{ $trend->title }}
                    </h2>

                    {{-- Summary --}}
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed flex-1">
                        {{ $trend->summary }}
                    </p>

                    {{-- Source --}}
                    @if($trend->source_label)
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            @if($trend->source_url)
                                <a href="{{ $trend->source_url }}" target="_blank" rel="noopener nofollow"
                                   class="text-xs text-gray-500 dark:text-gray-500 hover:text-primary transition-colors">
                                    via {{ $trend->source_label }} ↗
                                </a>
                            @else
                                <span class="text-xs text-gray-500 dark:text-gray-500">
                                    via {{ $trend->source_label }}
                                </span>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
