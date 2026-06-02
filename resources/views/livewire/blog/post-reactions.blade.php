<div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700"
     x-data="{
         counts: @js($counts),
         userReaction: @js($userReaction),
         pending: false,

         optimisticReact(type) {
             if (this.pending) return;
             this.pending = true;

             const oldReaction = this.userReaction;
             const oldCounts = { ...this.counts };

             if (oldReaction === type) {
                 this.counts[type] = Math.max(0, this.counts[type] - 1);
                 this.userReaction = null;
             } else {
                 if (oldReaction) {
                     this.counts[oldReaction] = Math.max(0, this.counts[oldReaction] - 1);
                 }
                 this.counts[type]++;
                 this.userReaction = type;
             }

             $wire.react(type).then(() => {
                 this.counts = $wire.counts;
                 this.userReaction = $wire.userReaction;
                 this.pending = false;
             }).catch(() => {
                 this.counts = oldCounts;
                 this.userReaction = oldReaction;
                 this.pending = false;
             });
         }
     }">

    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 mr-1">React:</span>

        @foreach(config('blog.reactions') as $type => $meta)
            @auth
                <button
                    x-on:click="optimisticReact('{{ $type }}')"
                    :class="userReaction === '{{ $type }}'
                        ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300 dark:border-indigo-600 ring-1 ring-indigo-200 dark:ring-indigo-700'
                        : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-sm transition-all duration-150 select-none"
                    :disabled="pending"
                    title="{{ $meta['label'] }}">
                    <span class="text-base leading-none" :class="userReaction === '{{ $type }}' ? 'scale-110' : ''" style="transition: transform 0.15s;">{{ $meta['emoji'] }}</span>
                    <span class="text-xs font-semibold tabular-nums"
                          :class="userReaction === '{{ $type }}' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400'"
                          x-text="counts['{{ $type }}'] || ''"></span>
                </button>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-150"
                   title="Log in to react">
                    <span class="text-base leading-none">{{ $meta['emoji'] }}</span>
                    @if(($counts[$type] ?? 0) > 0)
                        <span class="text-xs font-semibold tabular-nums text-gray-500 dark:text-gray-400">{{ $counts[$type] }}</span>
                    @endif
                </a>
            @endauth
        @endforeach
    </div>
</div>
