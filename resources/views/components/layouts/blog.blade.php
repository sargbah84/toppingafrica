<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png">
    <link rel="manifest" href="/img/site.webmanifest">
    <meta name="theme-color" content="#d60842">

    <title>{{ $title ?? \App\Models\Setting::get('site_name', config('app.name')) }} - {{ \App\Models\Setting::get('site_name', config('app.name')) }}</title>

    <x-seo-meta
        :title="($title ?? \App\Models\Setting::get('site_name', config('app.name'))) . ' - ' . \App\Models\Setting::get('site_name', config('app.name'))"
        :description="$metaDescription ?? \App\Models\Setting::get('site_description', 'News, Entertainment, Business & Culture')"
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
    <link rel="alternate" type="application/rss+xml" title="{{ \App\Models\Setting::get('site_name', config('app.name')) }} RSS Feed" href="{{ route('blog.feed') }}">

    {{-- Schema.org JSON-LD --}}
    @stack('schema')

    <style>
        :root {
            --color-primary: #d60842;
            --color-primary-hover: #b50636;
            --color-secondary: #2d3d8b;
        }

        /* Custom scrollbar for mobile menu */
        .mobile-menu-scroll {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }
        .mobile-menu-scroll:hover {
            scrollbar-color: rgba(255,255,255,0.2) transparent;
        }
        .mobile-menu-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .mobile-menu-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .mobile-menu-scroll::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 4px;
        }
        .mobile-menu-scroll:hover::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
        }
        .mobile-menu-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.35);
        }
    </style>

    {{-- Google Tag Manager / Analytics --}}
    @if ($gtmId = \App\Models\Setting::get('google_tag_manager_id'))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    @elseif ($gaId = \App\Models\Setting::get('google_analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Google reCAPTCHA Enterprise --}}
    @if(app(\App\Services\RecaptchaService::class)->isEnabled())
        <script src="https://www.google.com/recaptcha/enterprise.js?render={{ app(\App\Services\RecaptchaService::class)->getSiteKey() }}" defer></script>
    @endif
</head>
<body class="bg-white dark:bg-[#1f1f1f] text-gray-900 dark:text-gray-100 font-sans antialiased transition-colors duration-300">
    @if ($gtmId = \App\Models\Setting::get('google_tag_manager_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- Scroll Progress Bar --}}
    <div id="scroll-progress" class="fixed top-0 left-0 h-[3px] bg-primary z-[9999] transition-all duration-150" style="width: 0%"></div>

    {{-- Ad: Header (above navbar) --}}
    <div class="max-w-container mx-auto px-4">
        <x-ad-slot position="header" />
    </div>

    {{-- Header --}}
    @include('blog.partials.header')

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
               class="fixed top-0 left-0 w-80 h-full bg-[#1a1a1a] z-[999] overflow-y-auto shadow-2xl mobile-menu-scroll" x-cloak>
            <div class="p-6">
                {{-- Close Button --}}
                <button @click="open = false" class="text-gray-400 hover:text-white mb-8">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                {{-- Categories --}}
                <nav class="space-y-5">
                    <a href="{{ template_url('trending') }}"
                       class="block text-3xl font-black text-white hover:text-primary transition-colors leading-tight">
                        Trending
                    </a>
                    @php $menuCategories = \App\Models\Category::active()->ordered()->withCount('publishedPosts')->take(10)->get(); @endphp
                    @foreach($menuCategories as $cat)
                        <a href="{{ route('blog.category', $cat->slug) }}"
                           class="block text-3xl font-black text-white hover:text-primary transition-colors leading-tight">
                            {{ $cat->name }}
                            <sup class="text-xs font-semibold text-gray-500 ml-1">{{ $cat->published_posts_count }}</sup>
                        </a>
                    @endforeach
                </nav>

                {{-- Follow Us --}}
                @php
                    $socialLinks = [
                        ['key' => 'social_facebook', 'label' => 'Facebook', 'icon' => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 1.092.063 1.376.126v3.205c-.25-.023-.685-.035-1.225-.035-1.74 0-2.413.659-2.413 2.373v1.889h3.476l-.597 3.667h-2.879v8.07C18.62 23.1 22.5 18.012 22.5 12.07c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.476 3.672 10.096 8.601 11.521'],
                        ['key' => 'social_twitter', 'label' => 'X (Twitter)', 'icon' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
                        ['key' => 'social_youtube', 'label' => 'YouTube', 'icon' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12z'],
                        ['key' => 'social_instagram', 'label' => 'Instagram', 'icon' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z'],
                    ];
                @endphp
                <div class="mt-12">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-5">Follow Us</h3>
                    <div class="space-y-3">
                        @foreach ($socialLinks as $social)
                            @if ($url = \App\Models\Setting::get($social['key']))
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="flex items-center gap-3 text-sm text-gray-300 hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $social['icon'] }}"/></svg>
                                    {{ $social['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
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

    {{-- Go to Top Button --}}
    <div x-data="{ show: false }"
         @scroll.window="show = (window.scrollY > 400)"
         x-cloak>
        <button x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="fixed bottom-20 right-6 z-[990] w-10 h-10 bg-primary hover:bg-primary-hover text-white rounded-full shadow-lg flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
        </button>
    </div>

    {{-- Cookie Consent Banner --}}
    <div x-data="{ dismissed: localStorage.getItem('cookie_consent') === '1' }"
         x-show="!dismissed"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 z-[995] bg-[#1a1a1a] border-t border-gray-800 px-6 py-3"
         x-cloak>
        <div class="max-w-container mx-auto flex items-center justify-between gap-4">
            <p class="text-sm text-gray-300">Your experience on this site will be improved by allowing cookies.</p>
            <button @click="localStorage.setItem('cookie_consent', '1'); dismissed = true"
                    class="flex-shrink-0 px-5 py-2 border border-gray-600 text-sm text-gray-300 hover:bg-white hover:text-gray-900 transition-colors rounded-sm whitespace-nowrap">
                Allow cookies
            </button>
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

    <x-popup-renderer />
    @livewireScripts
</body>
</html>
