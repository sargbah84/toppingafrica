<x-layouts.blog title="My Account">
    <div class="max-w-container mx-auto px-4 py-10">

        {{-- Email Verification Banner --}}
        @if(!auth()->user()->hasVerifiedEmail())
            <div class="mb-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">Your email address is not verified.</p>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-0.5">Please verify your email to comment on articles. Check your inbox or request a new link.</p>
                        <a href="{{ route('verification.notice') }}" class="inline-block mt-2 text-sm font-semibold text-amber-800 dark:text-amber-200 underline hover:text-amber-900 dark:hover:text-amber-100">
                            Verify Email
                        </a>
                    </div>
                </div>
            </div>
        @endif

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
                    <button @click="tab = 'bookmarks'"
                            :class="tab === 'bookmarks' ? 'border-primary text-primary' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="pb-3 text-sm font-semibold border-b-2 transition-colors">
                        Bookmarks
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

            {{-- Bookmarks Tab --}}
            <div x-show="tab === 'bookmarks'" x-cloak>
                <livewire:account.my-bookmarks />
            </div>

            {{-- Comments Tab --}}
            <div x-show="tab === 'comments'" x-cloak>
                <livewire:account.my-comments />
            </div>
        </div>
    </div>
</x-layouts.blog>
