<div class="flex items-center gap-1" x-data="{ open: false }">
    {{-- Reaction summary (always visible) --}}
    <div class="relative">
        <button @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm transition-colors
                       {{ $userReaction ? 'bg-primary/10 text-primary border border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-transparent hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            @if($userReaction)
                <span class="text-base">{{ \App\Models\PostReaction::EMOJIS[$userReaction] }}</span>
            @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                </svg>
            @endif
            @if($this->total > 0)
                <span class="text-xs font-semibold">{{ $this->total }}</span>
            @endif
        </button>

        {{-- Reaction picker dropdown --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute bottom-full left-0 mb-2 flex items-center gap-0.5 px-2 py-1.5 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 z-50">
            @foreach(\App\Models\PostReaction::TYPES as $type)
                <button wire:click="react('{{ $type }}')" @click="open = false"
                        title="{{ ucfirst($type) }} ({{ $counts[$type] ?? 0 }})"
                        class="relative w-9 h-9 flex items-center justify-center rounded-full text-xl transition-transform hover:scale-125 hover:bg-gray-100 dark:hover:bg-gray-700
                               {{ $userReaction === $type ? 'bg-primary/10 ring-2 ring-primary/30' : '' }}">
                    {{ \App\Models\PostReaction::EMOJIS[$type] }}
                    @if(($counts[$type] ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 min-w-[16px] h-4 flex items-center justify-center px-0.5 text-[10px] font-bold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full">
                            {{ $counts[$type] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>
