{{-- Renders Livewire `notify` events: $this->dispatch('notify', type: 'success|error|warning|info', message: '...') --}}
<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: detail.type || 'info', message: detail.message || '' });
            setTimeout(() => this.remove(id), detail.type === 'error' ? 8000 : 4000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
    }"
    x-on:notify.window="add($event.detail)"
    class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-4 sm:w-96 z-50 flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition.opacity
            class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg text-sm"
            :class="{
                'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/80 dark:border-green-700 dark:text-green-100': toast.type === 'success',
                'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/80 dark:border-red-700 dark:text-red-100': toast.type === 'error',
                'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/80 dark:border-amber-700 dark:text-amber-100': toast.type === 'warning',
                'bg-white border-gray-200 text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100': !['success', 'error', 'warning'].includes(toast.type),
            }"
        >
            <p class="flex-1" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" class="opacity-60 hover:opacity-100" aria-label="Dismiss">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
