<div x-data="{
    facts: @js($facts),
    currentIndex: 0,
    flipped: false,
    touchStartX: 0,

    get total() { return this.facts.length; },
    get currentFact() { return this.facts[this.currentIndex]; },
    get progress() { return ((this.currentIndex + 1) / this.total) * 100; },

    next() {
        if (this.currentIndex < this.total - 1) {
            this.flipped = false;
            this.currentIndex++;
        }
    },
    prev() {
        if (this.currentIndex > 0) {
            this.flipped = false;
            this.currentIndex--;
        }
    },
    handleTouchStart(e) { this.touchStartX = e.touches[0].clientX; },
    handleTouchEnd(e) {
        const diff = this.touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) this.next();
            else this.prev();
        }
    }
}" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

    {{-- Progress --}}
    <div class="h-1 bg-gray-200 dark:bg-gray-700">
        <div class="h-1 bg-primary transition-all duration-300" :style="'width: ' + progress + '%'"></div>
    </div>

    <div class="p-6">
        {{-- Counter --}}
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold text-primary uppercase tracking-wide">Did You Know?</span>
            <span class="text-xs text-gray-400 dark:text-gray-500" x-text="(currentIndex + 1) + ' of ' + total"></span>
        </div>

        {{-- Card --}}
        <div class="relative min-h-[160px] flex items-center justify-center cursor-pointer select-none"
             @click="flipped = !flipped"
             @touchstart="handleTouchStart"
             @touchend="handleTouchEnd"
             style="perspective: 1000px;">

            {{-- Fact Side --}}
            <div x-show="!flipped"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="text-center px-4">
                <p class="text-lg font-semibold text-gray-900 dark:text-white leading-relaxed" x-text="currentFact.text"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-4" x-show="currentFact.source">
                    Tap to see source
                </p>
            </div>

            {{-- Source Side --}}
            <div x-show="flipped" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="text-center px-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Source</p>
                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="currentFact.source || 'No source provided'"></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-4">Tap to flip back</p>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center justify-between mt-6">
            <button @click="prev" :disabled="currentIndex === 0"
                    class="p-2 rounded-full text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>

            {{-- Dots --}}
            <div class="flex items-center gap-1.5">
                <template x-for="(_, i) in facts" :key="i">
                    <button @click="flipped = false; currentIndex = i"
                            class="w-2 h-2 rounded-full transition-all"
                            :class="i === currentIndex ? 'bg-primary w-4' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400'"></button>
                </template>
            </div>

            <button @click="next" :disabled="currentIndex === total - 1"
                    class="p-2 rounded-full text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
        </div>
    </div>

    {{-- Global source --}}
    @if($sourceUrl)
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-3">
            <a href="{{ $sourceUrl }}" target="_blank" rel="noopener" class="text-xs text-gray-400 hover:text-primary transition-colors">
                View full source &rarr;
            </a>
        </div>
    @endif
</div>
