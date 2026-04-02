<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Topping Africa' }} - {{ config('app.name') }}</title>

    <x-seo-meta
        :title="($title ?? 'Topping Africa') . ' - ' . config('app.name')"
        :description="$metaDescription ?? 'African News, Entertainment, Business & Culture'"
        :keywords="$keywords ?? null"
        :canonical="$canonical ?? null"
        :ogType="$ogType ?? 'website'"
        :ogImage="$ogImage ?? null"
        :ogImageAlt="$ogImageAlt ?? null"
        :twitterCard="$twitterCard ?? 'summary_large_image'"
        :author="$author ?? null"
        :publishedTime="$publishedTime ?? null"
        :modifiedTime="$modifiedTime ?? null"
        :section="$section ?? null"
        :tags="$tags ?? null"
        :noindex="$noindex ?? false"
    />

    {{-- RSS Feed --}}
    <link rel="alternate" type="application/rss+xml" title="{{ config('app.name', 'Topping Africa') }} RSS Feed" href="{{ route('blog.feed') }}">

    {{-- Schema.org JSON-LD --}}
    @stack('schema')

    <style>
        :root {
            --color-primary: #d60842;
            --color-primary-hover: #b50636;
            --color-secondary: #2d3d8b;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-white dark:bg-[#1f1f1f] text-gray-900 dark:text-gray-100 font-sans antialiased transition-colors duration-300">

    {{-- Scroll Progress Bar --}}
    <div id="scroll-progress" class="fixed top-0 left-0 h-[3px] bg-primary z-[9999] transition-all duration-150" style="width: 0%"></div>

    {{-- Header --}}
    @include('blog.partials.header')

    {{-- Ad: Header --}}
    <div class="max-w-container mx-auto px-4">
        <x-ad-slot position="header" />
    </div>

    {{-- Main Content --}}
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- Ad: Footer --}}
    <div class="max-w-container mx-auto px-4">
        <x-ad-slot position="footer" />
    </div>

    {{-- Footer --}}
    @include('blog.partials.footer')

    {{-- Mobile Menu Overlay --}}
    <div x-data="{ open: false }"
         @toggle-mobile-menu.window="open = !open"
         @keydown.escape.window="open = false">
        {{-- Backdrop --}}
        <div x-show="open" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false" class="fixed inset-0 bg-black/60 z-[998]" x-cloak></div>

        {{-- Mobile Menu Panel --}}
        <aside x-show="open" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
               class="fixed top-0 left-0 w-80 h-full bg-white dark:bg-gray-900 z-[999] overflow-y-auto shadow-2xl" x-cloak>
            <div class="p-6">
                <div class="flex justify-between items-center mb-8">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 dark:text-white">Topping Africa</a>
                    <button @click="open = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="space-y-1">
                    @php $categories = \App\Models\Category::active()->ordered()->take(10)->get(); @endphp
                    <a href="{{ route('home') }}" class="block px-3 py-2.5 text-sm font-semibold text-gray-900 dark:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">Home</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.category', $cat->slug) }}" class="block px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">{{ $cat->name }}</a>
                    @endforeach
                </nav>
            </div>
        </aside>
    </div>

    {{-- Search Overlay --}}
    <div x-data="{ open: false }"
         @toggle-search.window="open = !open"
         @keydown.escape.window="open = false">
        <div x-show="open" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-white/95 dark:bg-gray-900/95 z-[997] flex items-start justify-center pt-[20vh]" x-cloak>
            <div class="w-full max-w-2xl px-6">
                <form action="{{ route('blog.search') }}" method="GET">
                    <div class="relative">
                        <input type="text" name="q" placeholder="Search articles..." autofocus
                               class="w-full text-2xl md:text-3xl font-light bg-transparent border-0 border-b-2 border-gray-300 dark:border-gray-600 focus:border-primary focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 pb-4">
                        <button type="submit" class="absolute right-0 bottom-4 text-gray-400 hover:text-primary">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        </button>
                    </div>
                </form>
                <button @click="open = false" class="absolute top-8 right-8 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Scroll progress bar
        window.addEventListener('scroll', () => {
            const scrollTop = document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const progress = (scrollTop / scrollHeight) * 100;
            document.getElementById('scroll-progress').style.width = progress + '%';
        });
    </script>

    @livewireScripts
</body>
</html>
