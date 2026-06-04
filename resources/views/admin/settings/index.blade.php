<x-layouts.admin title="Settings" header="Settings">

    {{-- Monitoring Cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-8">
        {{-- Activity Logs --}}
        <a href="{{ route('admin.monitoring.activity-logs') }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition group">
            <div class="flex items-center justify-between mb-3">
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/30 p-2.5">
                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Activity Logs</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track staff logins, content changes, and actions.</p>
            <div class="flex items-center gap-4 mt-3 text-sm">
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($monitoring['activity_count']) }} <span class="font-normal text-gray-500">total</span></span>
                <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $monitoring['activity_today'] }} <span class="font-normal text-gray-500">today</span></span>
            </div>
        </a>

        {{-- Request Logs --}}
        <a href="{{ route('admin.monitoring.request-logs') }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition group">
            <div class="flex items-center justify-between mb-3">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 p-2.5">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                    </svg>
                </div>
                <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Request Logs</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitor requests and identify failing endpoints.</p>
            <div class="flex items-center gap-4 mt-3 text-sm">
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($monitoring['request_count']) }} <span class="font-normal text-gray-500">logged</span></span>
                <span class="font-semibold {{ $monitoring['request_errors'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $monitoring['request_errors'] }} <span class="font-normal text-gray-500">errors</span></span>
            </div>
        </a>

        {{-- Job Monitor --}}
        <a href="{{ route('admin.monitoring.jobs') }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 hover:ring-2 hover:ring-indigo-500 transition group">
            <div class="flex items-center justify-between mb-3">
                <div class="rounded-lg bg-green-50 dark:bg-green-900/30 p-2.5">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z"/>
                    </svg>
                </div>
                <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Job Monitor</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitor cron jobs, queues, and failed job retries.</p>
            <div class="flex items-center gap-4 mt-3 text-sm">
                <span class="font-semibold {{ $monitoring['pending_jobs'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">{{ $monitoring['pending_jobs'] }} <span class="font-normal text-gray-500">pending</span></span>
                <span class="font-semibold {{ $monitoring['failed_jobs'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $monitoring['failed_jobs'] }} <span class="font-normal text-gray-500">failed</span></span>
            </div>
        </a>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- General --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">General</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Name</label>
                    <input type="text" name="site_name" value="{{ $settings['site_name'] ?? '' }}" placeholder="My Blog" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tagline</label>
                    <input type="text" name="site_tagline" value="{{ $settings['site_tagline'] ?? '' }}" placeholder="Your site's short tagline" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Site Description</label>
                    <textarea name="site_description" rows="3" placeholder="A brief description of your site for SEO and social sharing" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $settings['site_description'] ?? '' }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SEO Keywords</label>
                    <textarea name="site_keywords" rows="2" placeholder="African creators, content creators Africa, African influencers, African business, ..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $settings['site_keywords'] ?? '' }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Comma-separated keywords used in the homepage <code>&lt;meta name="keywords"&gt;</code> tag.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" placeholder="hello@example.com" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                    <input type="text" name="site_address" value="{{ $settings['site_address'] ?? '' }}" placeholder="123 Main St, City, State" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Footer Text</label>
                    <input type="text" name="footer_text" value="{{ $settings['footer_text'] ?? '' }}" placeholder="&copy; 2024 Your Site. All Rights Reserved." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to use default: &copy; [year] [site name]. All Rights Reserved.</p>
                </div>
            </div>
        </div>

        {{-- Header Navigation Pages --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Header Navigation</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Tick the pages you want in the public top navigation bar. Drag rows to reorder.
                Override the menu label by typing in the right-hand input — leave it blank to use the page title.
                Built-in sections like Trending and Creators are pages too — find them in the list below.
            </p>
            @if ($headerPages->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No pages created yet. <a href="{{ route('admin.pages.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create a page</a> first.</p>
            @else
                <ul class="space-y-2 sortable-list max-h-96 overflow-y-auto overflow-x-auto pr-1" data-sortable="header-pages">
                    @foreach ($headerPages as $row)
                        @php $page = $row['page']; @endphp
                        <li class="sortable-item flex items-center gap-3 p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 min-w-max">
                            <span class="sortable-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Drag to reorder">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9-13a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm1 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                            </span>
                            <input type="checkbox" name="header_pages[]" value="{{ $page->id }}"
                                {{ $row['enabled'] ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $page->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">/{{ $page->slug }}</div>
                            </div>
                            <input type="text"
                                name="header_page_labels[{{ $page->id }}]"
                                value="{{ $row['label'] ?? '' }}"
                                placeholder="{{ $page->title }}"
                                maxlength="60"
                                class="w-36 sm:w-44 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="text"
                                name="header_page_badges[{{ $page->id }}]"
                                value="{{ $row['badge'] ?? '' }}"
                                placeholder="Badge"
                                maxlength="10"
                                class="w-20 text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Footer Links --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Footer Links (About Section)</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Select which pages to show in the footer's About section.</p>
            @if ($pages->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No pages created yet. <a href="{{ route('admin.pages.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create a page</a> first.</p>
            @else
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    @foreach ($pages as $page)
                        <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                            <input type="checkbox" name="footer_pages[]" value="{{ $page->id }}"
                                {{ in_array($page->id, $footerPageIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">/{{ $page->slug }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Social Media --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Social Media</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Facebook URL</label>
                    <input type="url" name="social_facebook" value="{{ $settings['social_facebook'] ?? '' }}" placeholder="https://facebook.com/yourpage" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Twitter / X URL</label>
                    <input type="url" name="social_twitter" value="{{ $settings['social_twitter'] ?? '' }}" placeholder="https://x.com/yourhandle" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Instagram URL</label>
                    <input type="url" name="social_instagram" value="{{ $settings['social_instagram'] ?? '' }}" placeholder="https://instagram.com/yourhandle" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">YouTube URL</label>
                    <input type="url" name="social_youtube" value="{{ $settings['social_youtube'] ?? '' }}" placeholder="https://youtube.com/@yourchannel" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">LinkedIn URL</label>
                    <input type="url" name="social_linkedin" value="{{ $settings['social_linkedin'] ?? '' }}" placeholder="https://linkedin.com/company/yourcompany" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6" x-data="{ trackingType: '{{ ($settings['google_tag_manager_id'] ?? '') ? 'gtm' : 'ga' }}' }">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO & Tracking</h3>
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tracking Method</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="trackingType" value="ga" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Google Analytics</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="trackingType" value="gtm" class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Google Tag Manager</span>
                        </label>
                    </div>
                </div>
                <div x-show="trackingType === 'ga'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Google Analytics ID</label>
                    <input type="text" name="google_analytics_id" value="{{ $settings['google_analytics_id'] ?? '' }}" placeholder="G-XXXXXXXXXX" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Your Google Analytics 4 measurement ID.</p>
                </div>
                <div x-show="trackingType === 'gtm'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Google Tag Manager ID</label>
                    <input type="text" name="google_tag_manager_id" value="{{ $settings['google_tag_manager_id'] ?? '' }}" placeholder="GTM-XXXXXXX" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">GTM includes Analytics, Ads, and any other tags you configure in Tag Manager.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Save Settings
            </button>
        </div>
    </form>

    {{-- Cache Management (super admin only) — use after a deploy if views,
         config, or routes aren't showing the latest changes. --}}
    @if(auth()->user()->is_super_admin)
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mt-8">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Cache Management</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            Use <strong>Refresh</strong> after a deploy if new views, config, or routes aren't showing up. The next request will be slightly slower while caches rebuild.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <form method="POST" action="{{ route('admin.settings.cache') }}"
                  x-data
                  x-on:submit="if(!confirm('Refresh all caches? Clears then rebuilds optimization cache.')) $event.preventDefault()">
                @csrf
                <input type="hidden" name="cache_action" value="refresh">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Refresh caches
                </button>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400"><code>optimize:clear</code> + <code>optimize</code></p>
            </form>

            <form method="POST" action="{{ route('admin.settings.cache') }}"
                  x-data
                  x-on:submit="if(!confirm('Clear all caches?')) $event.preventDefault()">
                @csrf
                <input type="hidden" name="cache_action" value="clear">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-white dark:bg-gray-700 px-3.5 py-2.5 text-sm font-semibold text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/>
                    </svg>
                    Clear only
                </button>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400"><code>optimize:clear</code></p>
            </form>

            <form method="POST" action="{{ route('admin.settings.cache') }}"
                  x-data
                  x-on:submit="if(!confirm('Rebuild optimize cache?')) $event.preventDefault()">
                @csrf
                <input type="hidden" name="cache_action" value="optimize">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-white dark:bg-gray-700 px-3.5 py-2.5 text-sm font-semibold text-gray-900 dark:text-white ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 4.5l-.75 6h5.25l-6.75 9 .75-6h-5.25Z"/>
                    </svg>
                    Rebuild optimize
                </button>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400"><code>optimize</code></p>
            </form>
        </div>
    </div>
    @endif

    {{-- Sortable.js for drag-to-reorder header nav lists --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.sortable-list').forEach(function (list) {
                new Sortable(list, {
                    animation: 150,
                    handle: '.sortable-handle',
                    ghostClass: 'opacity-40',
                    chosenClass: 'ring-2',
                    dragClass: 'shadow-lg',
                });
            });
        });
    </script>
</x-layouts.admin>
