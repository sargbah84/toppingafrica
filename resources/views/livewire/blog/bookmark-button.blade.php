<div>
    <button wire:click="toggle" title="{{ $isBookmarked ? 'Remove bookmark' : 'Save for later' }}"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm transition-colors
                   {{ $isBookmarked
                       ? 'bg-primary/10 text-primary border border-primary/30'
                       : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-transparent hover:bg-gray-200 dark:hover:bg-gray-700' }}">
        @if($isBookmarked)
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
            </svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
            </svg>
        @endif
        <span class="text-xs font-medium">{{ $isBookmarked ? 'Saved' : 'Save' }}</span>
    </button>
</div>
