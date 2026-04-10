<div class="{{ $variant === 'heart' ? 'absolute top-3 left-3 z-10' : 'inline-block' }}">
    @if ($variant === 'heart')
        <button type="button" wire:click="toggle"
                wire:loading.attr="disabled"
                aria-label="{{ $following ? 'Unsubscribe from' : 'Subscribe to' }} {{ $creator->name }}"
                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/90 dark:bg-gray-900/90 backdrop-blur shadow-sm hover:scale-110 transition-transform">
            @if ($following)
                <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
            @else
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
            @endif
        </button>
    @else
        {{-- Pill variant for the detail page --}}
        <button type="button" wire:click="toggle"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold transition-all duration-200 shadow-md
                    {{ $following
                        ? 'bg-white dark:bg-gray-800 text-primary border-2 border-primary hover:bg-red-50 dark:hover:bg-red-900/20'
                        : 'bg-gradient-to-r from-primary to-pink-600 text-white hover:shadow-xl hover:scale-105' }}">
            @if ($following)
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
                Subscribed
            @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Subscribe
            @endif
            <span class="ml-1 text-xs opacity-80">{{ number_format($count) }}</span>
        </button>
    @endif
</div>
