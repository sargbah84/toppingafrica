@php
    $headerCategories = \App\Models\Category::active()->ordered()->take(8)->get();
@endphp

<header x-data="{ sticky: false }"
        @scroll.window="sticky = (window.scrollY > 200)"
        :class="sticky ? 'fixed top-0 left-0 right-0 shadow-md bg-white/95 dark:bg-[#1f1f1f]/95 backdrop-blur-sm animate-slide-down z-[990]' : 'relative bg-white dark:bg-[#1f1f1f]'"
        class="border-b border-gray-100 dark:border-gray-800 transition-all duration-300">
    <div class="max-w-container mx-auto px-4">
        <div class="flex items-center justify-between h-16">

            {{-- Left: Mobile Menu Toggle + Desktop Nav --}}
            <div class="flex items-center gap-4">
                {{-- Mobile menu button --}}
                <button @click="$dispatch('toggle-mobile-menu')" class="lg:hidden text-gray-700 dark:text-gray-300 hover:text-primary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>

                {{-- Desktop Navigation --}}
                <nav class="hidden lg:flex items-center gap-1">
                    @foreach($headerCategories as $cat)
                        <a href="{{ route('blog.category', $cat->slug) }}"
                           class="px-3 py-2 text-[13px] font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Center: Logo --}}
            <a href="{{ route('home') }}" class="absolute left-1/2 -translate-x-1/2 lg:relative lg:left-auto lg:translate-x-0">
                <span class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                    Topping<span class="text-primary">Africa</span>
                </span>
            </a>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-3">
                {{-- Dark/Light Mode Toggle --}}
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                        class="p-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary border-r border-gray-200 dark:border-gray-700 pr-4 transition-colors">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                    </svg>
                </button>

                {{-- Search Toggle --}}
                <button @click="$dispatch('toggle-search')" class="p-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </button>
            </div>
        </div>
    </div>
</header>

<style>
    @keyframes slide-down { from { transform: translateY(-100%); } to { transform: translateY(0); } }
    .animate-slide-down { animation: slide-down 0.3s ease-out; }
</style>
