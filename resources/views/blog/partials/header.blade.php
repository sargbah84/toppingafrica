@php
    // Header nav items: a single ordered list of CMS pages picked by the
    // admin in Settings → Header Navigation. Built-in sections like Trending
    // and Creators live in this list too because they were converted into
    // template-backed CMS pages by the seed_template_pages migration.
    $savedHeaderPages = \App\Http\Controllers\Admin\SettingController::normaliseSavedHeaderPages(
        json_decode(\App\Models\Setting::get('header_pages', '[]'), true) ?: []
    );
    $headerPageIds = array_column($savedHeaderPages, 'id');
    $headerPages = ! empty($headerPageIds)
        ? \App\Models\Page::whereIn('id', $headerPageIds)
            ->where('status', 'published')
            ->get(['id', 'title', 'slug'])
            ->keyBy('id')
        : collect();
    $headerPagesOrdered = collect($savedHeaderPages)
        ->filter(fn($row) => $headerPages->has($row['id']))
        ->map(function ($row) use ($headerPages) {
            $page = $headerPages->get($row['id']);
            return [
                'page' => $page,
                'label' => $row['label'] ?? $page->title,
            ];
        })
        ->values();
@endphp

<header x-data="{ sticky: false }"
        @scroll.window="sticky = (window.scrollY > 200)"
        :class="sticky ? 'fixed top-0 left-0 right-0 shadow-md bg-white/95 dark:bg-[#1f1f1f]/95 backdrop-blur-sm animate-slide-down z-[990]' : 'relative bg-white dark:bg-[#1f1f1f]'"
        class="border-b border-gray-100 dark:border-gray-800 transition-all duration-300">
    <div class="max-w-container mx-auto px-4">
        <div class="flex items-center justify-between h-20">

            {{-- Left: Hamburger + Nav --}}
            <div class="flex items-center gap-6">
                {{-- Hamburger menu --}}
                <button @click="$dispatch('toggle-mobile-menu')" class="text-gray-700 dark:text-gray-300 hover:text-primary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>

                {{-- Desktop Nav --}}
                <nav class="hidden md:flex items-center gap-6">
                    @foreach($headerPagesOrdered as $row)
                        @php $page = $row['page']; @endphp
                        <a href="{{ url('/' . $page->slug) }}"
                           class="text-sm font-semibold {{ request()->is($page->slug) ? 'text-primary' : 'text-gray-800 dark:text-gray-200 hover:text-primary dark:hover:text-primary' }} transition-colors">
                            {{ $row['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Center: Logo Image --}}
            <a href="{{ route('home') }}" class="absolute left-1/2 -translate-x-1/2">
                <img src="{{ asset('img/topping-africa-logo.png') }}" alt="{{ \App\Models\Setting::get('site_name', config('app.name')) }}" class="h-12 dark:brightness-0 dark:invert">
            </a>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-4">
                {{-- Dark/Light Mode --}}
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                        class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                    </svg>
                </button>

                {{-- Search --}}
                <button @click="$dispatch('toggle-search')" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </button>

                {{-- Login / User Menu --}}
                @guest
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex items-center px-5 py-2 border border-gray-900 dark:border-white text-gray-900 dark:text-white text-xs font-semibold uppercase tracking-wide rounded-sm hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-gray-900 transition-colors">
                        Login
                    </a>
                @else
                    <div class="relative" x-data="{ userMenu: false }" @click.away="userMenu = false">
                        <button @click="userMenu = !userMenu" class="flex items-center">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                                 class="w-9 h-9 rounded-full object-cover border-2 border-transparent hover:border-primary transition-colors">
                        </button>
                        <div x-show="userMenu" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute right-0 top-full mt-3 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-100 dark:border-gray-700 py-1 z-50">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                My Account
                            </a>
                            @if(auth()->user()->is_staff || auth()->user()->is_super_admin)
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                                    Admin Dashboard
                                </a>
                            @endif
                            <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Account Settings
                            </a>
                            <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</header>

<style>
    @keyframes slide-down { from { transform: translateY(-100%); } to { transform: translateY(0); } }
    .animate-slide-down { animation: slide-down 0.3s ease-out; }
</style>
