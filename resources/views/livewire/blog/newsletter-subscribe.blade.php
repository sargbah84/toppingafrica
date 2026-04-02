<div>
    @if($successMessage)
        <p class="text-sm text-green-400 mb-2">{{ $successMessage }}</p>
    @endif
    @if($errorMessage)
        <p class="text-sm text-red-400 mb-2">{{ $errorMessage }}</p>
    @endif
    @error('email')
        <p class="text-sm text-red-400 mb-2">{{ $message }}</p>
    @enderror

    <form wire:submit="subscribe" class="space-y-2">
        <div class="flex">
            <input type="email" wire:model="email" placeholder="Your email"
                   class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 text-sm text-white rounded-l-md focus:border-primary focus:ring-0 placeholder-gray-500"
                   required>
            <button type="submit"
                    class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-r-md hover:bg-primary-hover transition-colors"
                    wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                <span wire:loading wire:target="subscribe">...</span>
            </button>
        </div>
        <input type="text" wire:model="name" placeholder="Your name (optional)"
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 text-sm text-white rounded-md focus:border-primary focus:ring-0 placeholder-gray-500">
    </form>
</div>
