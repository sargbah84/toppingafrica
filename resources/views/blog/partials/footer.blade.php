{{-- Instagram Follow Section --}}
@if ($instagramUrl = \App\Models\Setting::get('social_instagram'))
<section class="py-12 text-center">
    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:text-primary-hover transition-colors">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
        <span class="text-sm font-semibold">{{ str_replace(['https://instagram.com/', 'https://www.instagram.com/'], '@', $instagramUrl) }}</span>
    </a>
    <h3 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mt-3">Follow Us On Instagram</h3>
</section>
@endif

{{-- Footer --}}
<footer class="bg-[#252527] text-gray-300 pt-14 pb-8">
    <div class="max-w-container mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-12 mb-14">
            {{-- Left: Logo + Address + Subscribe --}}
            <div class="lg:w-[220px] flex-shrink-0">
                <div class="flex items-start gap-4 mb-5">
                    <div class="w-[100px] h-[120px] flex-shrink-0 overflow-hidden rounded-sm">
                        <img src="{{ asset('img/topping-africa-logomark.png') }}" alt="{{ \App\Models\Setting::get('site_name', config('app.name')) }}" class="w-full h-full object-cover">
                    </div>
                    <div class="pt-2">
                        <h4 class="text-white text-xl font-black leading-tight tracking-tight" style="font-family: Georgia, serif;">{{ \App\Models\Setting::get('site_name', config('app.name')) }}</h4>
                        @if ($tagline = \App\Models\Setting::get('site_tagline'))
                            <p class="text-xs text-primary italic mt-1">{{ $tagline }}</p>
                        @endif
                    </div>
                </div>
                @if ($address = \App\Models\Setting::get('site_address'))
                    <p class="text-sm text-gray-400 mb-5">{!! nl2br(e($address)) !!}</p>
                @endif
                <a href="#" class="inline-block px-7 py-2.5 border border-gray-500 text-sm text-gray-300 hover:bg-white hover:text-gray-900 transition-colors rounded-sm">
                    Subscribe
                </a>
            </div>

            {{-- Right: Link Columns --}}
            <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-8 lg:gap-6">
                {{-- About --}}
                @php
                    $footerPageIds = json_decode(\App\Models\Setting::get('footer_pages', '[]'), true) ?: [];
                    $footerPages = $footerPageIds ? \App\Models\Page::whereIn('id', $footerPageIds)->published()->orderBy('title')->get() : collect();
                @endphp
                @if ($footerPages->isNotEmpty())
                <div>
                    <h5 class="text-white text-xs font-bold uppercase tracking-wider mb-5">About</h5>
                    <ul class="space-y-3">
                        @foreach ($footerPages as $fPage)
                            <li><a href="/{{ $fPage->slug }}" class="text-sm text-gray-400 hover:text-white transition-colors">{{ $fPage->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- News --}}
                <div>
                    <h5 class="text-primary text-xs font-bold uppercase tracking-wider mb-5">News</h5>
                    <ul class="space-y-3">
                        <li><a href="{{ route('blog.category', 'technology-innovation') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Technology</a></li>
                        <li><a href="{{ route('blog.category', 'travel-tourism') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Travel</a></li>
                        <li><a href="{{ route('blog.category', 'agriculture-food-security') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Food</a></li>
                        <li><a href="{{ route('blog.category', 'fashion') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Fashion</a></li>
                    </ul>
                </div>

                {{-- Travel --}}
                <div>
                    <h5 class="text-primary text-xs font-bold uppercase tracking-wider mb-5">Travel</h5>
                    <ul class="space-y-3">
                        <li><a href="{{ route('blog.category', 'health-wellness') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Health & Fitness</a></li>
                        <li><a href="{{ route('blog.category', 'technology-finance') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Finance</a></li>
                        <li><a href="{{ route('blog.category', 'art-design') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Art & Design</a></li>
                        <li><a href="{{ route('blog.category', 'photography') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Photography</a></li>
                    </ul>
                </div>

                {{-- Healthy --}}
                <div>
                    <h5 class="text-primary text-xs font-bold uppercase tracking-wider mb-5">Healthy</h5>
                    <ul class="space-y-3">
                        <li><a href="{{ route('blog.category', 'music') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Music</a></li>
                        <li><a href="{{ route('blog.category', 'books-literature') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Books & Literature</a></li>
                        <li><a href="{{ route('blog.category', 'business-economy') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Business</a></li>
                        <li><a href="{{ route('blog.category', 'education-youth') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Education</a></li>
                    </ul>
                </div>

                {{-- More --}}
                <div>
                    <h5 class="text-primary text-xs font-bold uppercase tracking-wider mb-5">&nbsp;</h5>
                    <ul class="space-y-3">
                        <li><a href="{{ route('blog.category', 'lifestyle-culture') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Lifestyle & Culture</a></li>
                        <li><a href="{{ route('blog.category', 'society-human-rights') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Society & Human Rights</a></li>
                        <li><a href="{{ route('blog.category', 'science-environment') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Science & Environment</a></li>
                        <li><a href="{{ route('blog.category', 'politics-governance') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Politics & Governance</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Bottom --}}
        <div class="border-t border-gray-700 pt-6 text-center">
            <p class="text-xs text-gray-500">
                {!! \App\Models\Setting::get('footer_text', '&copy;' . date('Y') . ' ' . e(\App\Models\Setting::get('site_name', config('app.name'))) . '. All Rights Reserved.') !!}
            </p>
        </div>
    </div>
</footer>
