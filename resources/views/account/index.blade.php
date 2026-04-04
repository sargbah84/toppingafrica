<x-layouts.blog title="My Account">
    <div class="max-w-container mx-auto px-4 py-10">

        {{-- Account Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 mb-10">
            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                 class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ auth()->user()->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ auth()->user()->email }}</p>
                <div class="flex items-center gap-4 mt-3">
                    <a href="{{ route('profile') }}"
                       class="text-sm font-medium text-primary hover:text-primary-hover transition-colors">
                        Account Settings
                    </a>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Member since {{ auth()->user()->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div x-data="{ tab: 'history' }" class="space-y-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-8 -mb-px">
                    <button @click="tab = 'history'"
                            :class="tab === 'history' ? 'border-primary text-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="pb-3 text-sm font-semibold border-b-2 transition-colors">
                        Reading History
                    </button>
                    <button @click="tab = 'comments'"
                            :class="tab === 'comments' ? 'border-primary text-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="pb-3 text-sm font-semibold border-b-2 transition-colors">
                        My Comments
                    </button>
                </nav>
            </div>

            {{-- Reading History Tab --}}
            <div x-show="tab === 'history'" x-cloak>
                <livewire:account.reading-history />
            </div>

            {{-- Comments Tab --}}
            <div x-show="tab === 'comments'" x-cloak>
                <livewire:account.my-comments />
            </div>
        </div>
    </div>
</x-layouts.blog>
