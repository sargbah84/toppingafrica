<div>
    @if($successMessage)
        <p class="text-sm text-green-600 dark:text-green-400 mb-2">{{ $successMessage }}</p>
    @endif
    @if($errorMessage)
        <p class="text-sm text-red-600 dark:text-red-400 mb-2">{{ $errorMessage }}</p>
    @endif
    @error('email')
        <p class="text-sm text-red-600 dark:text-red-400 mb-2">{{ $message }}</p>
    @enderror
    @error('turnstile')
        <p class="text-sm text-red-600 dark:text-red-400 mb-2">{{ $message }}</p>
    @enderror

    <form wire:submit="subscribe" class="space-y-2">
        <input type="email" wire:model="email" placeholder="Your email"
               class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-sm text-gray-900 dark:text-white rounded-md focus:border-primary focus:ring-0 placeholder-gray-400 dark:placeholder-gray-500"
               required>
        <input type="text" wire:model="name" placeholder="Your name (optional)"
               class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-sm text-gray-900 dark:text-white rounded-md focus:border-primary focus:ring-0 placeholder-gray-400 dark:placeholder-gray-500">
        <x-turnstile action="newsletter_subscribe" :livewire="true" class="mb-3" />
        <button type="submit"
                class="w-full px-4 py-2 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary-hover transition-colors"
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="subscribe">Subscribe</span>
            <span wire:loading wire:target="subscribe">...</span>
        </button>
    </form>
</div>
