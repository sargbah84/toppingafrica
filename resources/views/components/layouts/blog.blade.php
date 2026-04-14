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

    @if(session()->has('impersonating_from'))
        <div class="sticky top-0 z-[9999] bg-amber-500 text-amber-950 text-center text-sm font-semibold py-2 px-4 flex items-center justify-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
            </svg>
            <span>Impersonating <strong>{{ auth()->user()->name }}</strong></span>
            <form method="POST" action="{{ route('admin.impersonate.leave') }}" class="inline">
                @csrf
                <button type="submit" class="ml-2 inline-flex items-center rounded-md bg-amber-800 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-900 transition">
                    Leave Impersonation
                </button>
            </form>
        </div>
    @endif

    {{-- Scroll Progress Bar --}}
    <div id="scroll-progress" class="fixed top-0 left-0 h-[3px] bg-primary z-[9999] transition-all duration-150" style="width: 0%"></div>

    {{-- Ad: Header (above navbar) --}}
    <div class="max-w-container mx-auto px-4">
        <x-ad-slot position="header" />
    </div>

    {{-- Header --}}
    @include('blog.partials.header')

    {{-- Email verification banner (shown site-wide for unverified logged-in users) --}}
    @auth
        @if(!auth()->user()->hasVerifiedEmail())
            <livewire:email-verification-banner />
        @endif
    @endauth

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

                {{-- Login (mobile only, guests only) --}}
                @guest
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 border border-white text-white text-xs font-semibold uppercase tracking-wide rounded-sm hover:bg-white hover:text-gray-900 transition-colors mb-8">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        Login
                    </a>
                @endguest

                {{-- Categories --}}
                @php
                    $mobileHeaderPages = \App\Http\Controllers\Admin\SettingController::normaliseSavedHeaderPages(
                        json_decode(\App\Models\Setting::get('header_pages', '[]'), true) ?: []
                    );
                    $mobileHeaderBadges = collect($mobileHeaderPages)->pluck('badge', 'id')->filter();
                    $creatorPage = \App\Models\Page::where('slug', 'creators')->first();
                    $creatorBadge = $creatorPage ? ($mobileHeaderBadges[$creatorPage->id] ?? null) : null;
                    $menuCategories = \App\Models\Category::active()->ordered()->withCount('publishedPosts')->take(10)->get();
                @endphp
                <nav class="space-y-5">
                    <a href="{{ template_url('trending') }}"
                       class="block text-3xl font-black text-white hover:text-primary transition-colors leading-tight">
                        Trending
                    </a>
                    <a href="{{ template_url('creators') }}"
                       class="relative inline-block text-3xl font-black text-white hover:text-primary transition-colors leading-tight">
                        Creators
                        @if($creatorBadge)
                            <span class="ml-2 inline-block align-middle px-2 py-0.5 text-[10px] font-bold uppercase leading-none tracking-wide rounded-full bg-primary text-white">{{ $creatorBadge }}</span>
                        @endif
                    </a>
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
    @php
        // Featured creators shown below the search input (empty state).
        // Queried once here so the overlay has content on open — the live
        // search-as-you-type replaces this list when the user types 2+ chars.
        $searchFeaturedCreators = \App\Models\Creator::query()
            ->where(fn ($q) => $q->where('status', 'published')->orWhere('status', 'claimed'))
            ->where(fn ($q) => $q->whereHas('media', fn ($m) => $m->where('collection_name', 'profile_image'))
                ->orWhere('profile_image_url', '!=', ''))
            ->orderByDesc('follower_count')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'category', 'country', 'profile_image_url', 'follower_count']);
    @endphp
    <div x-data="{
             open: false,
             query: '',
             creators: [],
             posts: [],
             loading: false,
             debounceId: null,
             get hasResults() { return this.creators.length > 0 || this.posts.length > 0; },
             onInput() {
                 clearTimeout(this.debounceId);
                 const q = this.query.trim();
                 if (q.length < 2) { this.creators = []; this.posts = []; this.loading = false; return; }
                 this.loading = true;
                 this.debounceId = setTimeout(() => {
                     fetch('{{ route('blog.suggest') }}?q=' + encodeURIComponent(q))
                         .then(r => r.json())
                         .then(data => {
                             this.creators = data.creators || [];
                             this.posts = data.posts || [];
                             this.loading = false;
                         })
                         .catch(() => { this.loading = false; });
                 }, 250);
             }
         }"
         @toggle-search.window="open = !open; if (open) $nextTick(() => $refs.searchInput && $refs.searchInput.focus())"
         @keydown.escape.window="open = false">
        <div x-show="open" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-white/95 dark:bg-gray-900/95 z-[997] overflow-y-auto" x-cloak>
            <div class="min-h-full flex flex-col items-center pt-[15vh] pb-16">
                <div class="w-full max-w-3xl px-6">
                    <form action="{{ route('blog.search') }}" method="GET">
                        <div class="relative">
                            <input type="text" name="q" x-model="query" x-ref="searchInput" @input="onInput()"
                                   placeholder="Search articles, creators..."
                                   class="w-full text-2xl md:text-3xl font-light bg-transparent border-0 border-b-2 border-gray-300 dark:border-gray-600 focus:border-primary focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400 pb-4">
                            <button type="submit" class="absolute right-0 bottom-4 text-gray-400 hover:text-primary">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                            </button>
                        </div>
                    </form>

                    {{-- Live results (search-as-you-type) --}}
                    <div x-show="query.trim().length >= 2" x-cloak class="mt-8">
                        <div x-show="loading" class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 py-4">Searching…</div>

                        <div x-show="!loading && !hasResults" class="text-sm text-gray-400 italic py-4">
                            No matches. Press Enter to run a full search.
                        </div>

                        {{-- Creators section --}}
                        <div x-show="!loading && creators.length > 0" class="mb-6">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Creators</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                                <template x-for="c in creators" :key="'c-' + c.slug">
                                    <a :href="c.url" class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 hover:border-primary transition-colors text-center">
                                        <div class="mx-auto w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <template x-if="c.image">
                                                <img :src="c.image" :alt="c.name" loading="lazy" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!c.image">
                                                <span class="text-white text-sm font-bold" x-text="c.initials"></span>
                                            </template>
                                        </div>
                                        <h4 class="mt-2 text-xs font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors truncate" x-text="c.name"></h4>
                                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400 truncate" x-text="[c.category, c.country].filter(Boolean).join(' · ')"></p>
                                    </a>
                                </template>
                            </div>
                        </div>

                        {{-- Articles section --}}
                        <div x-show="!loading && posts.length > 0">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Articles</h3>
                            <div class="space-y-2">
                                <template x-for="p in posts" :key="'p-' + p.url">
                                    <a :href="p.url" class="group flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <div class="shrink-0 w-16 h-16 rounded-md overflow-hidden bg-gray-200 dark:bg-gray-700">
                                            <template x-if="p.image">
                                                <img :src="p.image" :alt="p.title" loading="lazy" class="w-full h-full object-cover">
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors line-clamp-2" x-text="p.title"></h4>
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-1" x-text="p.excerpt"></p>
                                            <p class="mt-1 text-[11px] text-gray-400" x-show="p.author" x-text="'by ' + p.author"></p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </div>

                        {{-- "See all results" link --}}
                        <div x-show="!loading && hasResults" class="mt-5 text-center">
                            <a :href="'{{ route('blog.search') }}?q=' + encodeURIComponent(query)" class="text-sm font-semibold text-primary hover:underline">
                                See all results for "<span x-text="query"></span>" →
                            </a>
                        </div>
                    </div>

                    {{-- Empty state carousel — featured creators when no query --}}
                    @if($searchFeaturedCreators->count())
                        <div x-show="query.trim().length < 2" x-cloak class="mt-10">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Featured Creators</h3>
                                <a href="{{ url('/creators') }}" class="text-xs font-semibold text-primary hover:underline">View all →</a>
                            </div>
                            <div x-data="{
                                     scrollBy(dir) {
                                         this.$refs.track.scrollBy({ left: dir * 320, behavior: 'smooth' });
                                     }
                                 }" class="relative">
                                <button type="button" @click="scrollBy(-1)"
                                        class="hidden md:flex absolute -left-4 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 items-center justify-center text-gray-600 dark:text-gray-300 hover:text-primary"
                                        aria-label="Scroll left">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                </button>
                                <div x-ref="track" class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth -mx-2 px-2 pb-3" style="scrollbar-width: none;">
                                    @foreach($searchFeaturedCreators as $creator)
                                        <a href="{{ route('creators.show', $creator->slug) }}"
                                           class="group shrink-0 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-primary transition-colors text-center snap-start">
                                            <div class="mx-auto w-16 h-16 rounded-full overflow-hidden">
                                                @if($creator->profile_image_url)
                                                    <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold" style="background-color: {{ $creator->avatar_color }}">
                                                        {{ $creator->initials }}
                                                    </div>
                                                @endif
                                            </div>
                                            <h4 class="mt-2.5 text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors truncate">{{ $creator->name }}</h4>
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ $creator->category }}{{ $creator->country ? ' · ' . $creator->country : '' }}
                                            </p>
                                            @php($followerShort = \App\Support\FollowerFormat::short($creator->follower_count))
                                            @if($followerShort)
                                                <p class="mt-0.5 text-[11px] font-semibold text-primary">{{ $followerShort }} followers</p>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                                <button type="button" @click="scrollBy(1)"
                                        class="hidden md:flex absolute -right-4 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 items-center justify-center text-gray-600 dark:text-gray-300 hover:text-primary"
                                        aria-label="Scroll right">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

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
