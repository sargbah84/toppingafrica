<x-layouts.blog
    :title="$creator->name . ' — ' . $creator->category . ' Creator from ' . $creator->country"
    :metaDescription="Str::limit($creator->bio, 155)"
    :ogType="'profile'"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Person",
    "name": "{{ $creator->name }}",
    "description": "{{ Str::limit($creator->bio, 200) }}",
    "nationality": {
        "@@type": "Country",
        "name": "{{ $creator->country }}"
    },
    "url": "{{ url($creator->public_url) }}",
    @if($creator->profile_image_url)
    "image": "{{ $creator->profile_image_url }}",
    @endif
    "sameAs": [
        @foreach($creator->socialLinks as $link)
            "{{ $link->url }}"{{ !$loop->last ? ',' : '' }}
        @endforeach
    ]
}
</script>
@endpush

@php
    $shareUrl = url($creator->public_url);
    $shareText = $creator->name . ' on Topping Africa';
@endphp

<section class="max-w-container mx-auto px-4 py-8">
    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ template_url('creators') }}" class="hover:text-primary transition-colors">Creators</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 dark:text-white">{{ $creator->name }}</span>
    </nav>

    <div class="max-w-3xl mx-auto">
        {{-- Profile Card --}}
        <div class="relative"
             x-data="{ shareOpen: false, qrOpen: false, claimOpen: {{ session('success') || $errors->has('email') || $errors->has('recaptcha') ? 'true' : 'false' }} }"
             x-on:creator-updated.window="setTimeout(() => window.location.reload(), 300)"
             x-on:open-qr-modal.window="qrOpen = true">

            {{-- Top-right action — four states:
                 1. Can edit (owner/staff/admin) → Quick Edit Livewire component
                 2. Claimed but viewer is a stranger → disabled "Claimed" pill
                 3. Unclaimed → "Claim Profile" button (opens modal) --}}
            <div class="absolute top-4 right-4 z-20">
                @if($canEdit)
                    <livewire:creator-quick-edit :creator="$creator" :key="'quick-edit-'.$creator->id" />
                @elseif($isClaimed)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-semibold text-gray-500 dark:text-gray-400 cursor-default" title="This profile has been claimed by its owner">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Claimed
                    </span>
                @else
                    <button type="button" @click="claimOpen = true"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-semibold text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                        Claim Profile
                    </button>
                @endif
            </div>

            {{-- Avatar (centered) --}}
            <div class="relative pt-12 flex justify-center px-6">
                <div class="relative">
                    <div class="w-32 h-32 sm:w-36 sm:h-36 rounded-full overflow-hidden">
                        @if($creator->getFirstMediaUrl('profile_image'))
                            <img src="{{ $creator->getFirstMediaUrl('profile_image') }}" alt="{{ $creator->name }}"
                                 class="w-full h-full object-cover">
                        @elseif($creator->getRawOriginal('profile_image_url'))
                            <img src="{{ $creator->getRawOriginal('profile_image_url') }}" alt="{{ $creator->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-white text-4xl font-bold"
                                 style="background-color: {{ $creator->avatar_color }}">
                                {{ $creator->initials }}
                            </div>
                        @endif
                    </div>

                    {{-- Featured/Trending/Rising marker --}}
                    @if($creator->is_featured)
                        <span class="absolute -bottom-1 -right-1 inline-flex items-center justify-center w-9 h-9 rounded-full bg-yellow-400 text-white shadow-lg ring-4 ring-white dark:ring-gray-800" title="Featured">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 0 0 .95.69h4.16c.969 0 1.371 1.24.588 1.81l-3.366 2.446a1 1 0 0 0-.364 1.118l1.286 3.957c.3.922-.755 1.688-1.54 1.118l-3.366-2.446a1 1 0 0 0-1.176 0l-3.366 2.446c-.785.57-1.84-.196-1.54-1.118l1.286-3.957a1 1 0 0 0-.364-1.118L2.05 9.384c-.783-.57-.38-1.81.588-1.81h4.16a1 1 0 0 0 .95-.69l1.286-3.957Z"/></svg>
                        </span>
                    @elseif($creator->is_trending)
                        <span class="absolute -bottom-1 -right-1 inline-flex items-center justify-center px-2 h-7 rounded-full bg-pink-500 text-white text-[10px] font-bold shadow-lg ring-4 ring-white dark:ring-gray-800">HOT</span>
                    @elseif($creator->is_rising)
                        <span class="absolute -bottom-1 -right-1 inline-flex items-center justify-center px-2 h-7 rounded-full bg-orange-500 text-white text-[10px] font-bold shadow-lg ring-4 ring-white dark:ring-gray-800">NEW</span>
                    @endif
                </div>
            </div>

            {{-- Name + Category + Location --}}
            <div class="mt-4 text-center px-6">
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white">{{ $creator->name }}</h1>
                <p class="mt-1 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $creator->category }} Creator</p>
                <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                    {{ $creator->country }}
                </p>
            </div>

            {{-- Stats --}}
            <div class="mt-6 flex items-center justify-center gap-8 sm:gap-12 px-6">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-1.5 text-gray-400 dark:text-gray-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path fill-rule="evenodd" d="M.664 10.59a1.65 1.65 0 0 1 0-1.18l.879-2.197a4.875 4.875 0 0 1 4.523-3.067h7.868a4.875 4.875 0 0 1 4.523 3.067l.879 2.197a1.65 1.65 0 0 1 0 1.18l-.879 2.197a4.875 4.875 0 0 1-4.523 3.067H6.066a4.875 4.875 0 0 1-4.523-3.067L.664 10.59ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/></svg>
                        <span class="text-[11px] uppercase tracking-wider font-semibold">Followers</span>
                    </div>
                    @php($followerShort = \App\Support\FollowerFormat::short($creator->display_follower_count))
                    <div class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ $followerShort ?? '—' }}</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center gap-1.5 text-gray-400 dark:text-gray-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/></svg>
                        <span class="text-[11px] uppercase tracking-wider font-semibold">Profile</span>
                    </div>
                    <div class="mt-1 text-2xl font-black text-gray-900 dark:text-white">{{ ucfirst($creator->category) }}</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center gap-1.5 text-gray-400 dark:text-gray-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z"/></svg>
                        <span class="text-[11px] uppercase tracking-wider font-semibold">Status</span>
                    </div>
                    <div class="mt-1 text-2xl font-black {{ $creator->status === 'claimed' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $creator->status === 'claimed' ? 'Verified' : 'Active' }}
                    </div>
                </div>
            </div>

            {{-- Action buttons: QR Card → Subscribe → Share --}}
            <div class="mt-6 flex items-center justify-center gap-3 px-6">
                {{-- QR Card button — opens branded share card modal --}}
                <button type="button" @click="qrOpen = true"
                        class="group relative inline-flex items-center gap-2 p-3 sm:px-6 sm:py-3 rounded-full text-sm font-bold text-white bg-gradient-to-r from-primary to-secondary hover:opacity-90 transition shadow-sm"
                        title="Get a shareable QR card"
                        aria-label="QR Card">
                    <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/>
                    </svg>
                    <span class="hidden sm:inline">QR Card</span>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-yellow-300 ring-2 ring-white group-hover:animate-ping"></span>
                </button>

                <livewire:creator-follow-button :creator="$creator" variant="pill" :key="'follow-show-'.$creator->id" />

                {{-- Share dropdown --}}
                <div class="relative">
                    <button type="button" @click="shareOpen = !shareOpen"
                            class="inline-flex items-center gap-2 p-3 sm:px-6 sm:py-3 rounded-full text-sm font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition shadow-sm"
                            aria-label="Share">
                        <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                        <span class="hidden sm:inline">Share</span>
                    </button>

                    <div x-show="shareOpen" x-cloak @click.outside="shareOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 sm:left-1/2 sm:-translate-x-1/2 mt-2 w-56 origin-top rounded-xl bg-white dark:bg-gray-800 shadow-xl ring-1 ring-black/5 dark:ring-white/10 z-30">
                        <div class="py-2">
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareText) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                Share on X
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/></svg>
                                Share on Facebook
                            </a>
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($shareText . ' ' . $shareUrl) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                Share on WhatsApp
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.063 2.063 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                Share on LinkedIn
                            </a>
                            <button type="button"
                                    @click="navigator.clipboard.writeText('{{ $shareUrl }}'); shareOpen = false; window.tcModal && window.tcModal.alert('Link copied to clipboard!')"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                                Copy link
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subscribe prompt --}}
            <p class="mt-4 text-center text-[11px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500">
                Subscribe to keep updated on this creator
            </p>

            {{-- Wikimedia attribution (if applicable) --}}
            @if(!$creator->getFirstMediaUrl('profile_image') && $creator->getRawOriginal('profile_image_url') && $creator->profile_image_attribution)
                <p class="mt-2 text-[10px] text-gray-400 dark:text-gray-500 text-center px-6">
                    Photo: {{ $creator->profile_image_attribution }}
                    @if($creator->profile_image_license)
                        ({{ $creator->profile_image_license }})
                    @endif
                </p>
            @endif

            {{-- Divider --}}
            <div class="mt-6 mx-auto w-16 border-t border-gray-200 dark:border-gray-700"></div>

            {{-- About — breaks out of the profile card (max-w-3xl ≈ 768px) to ~85% of the
                 section's max-w-container (1240px) on lg+ via negative horizontal margins. --}}
            <div class="px-6 sm:px-10 py-6 lg:-mx-36">
                <h2 class="text-center text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">About Me</h2>
                <p class="text-center text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                    {{ $creator->bio }}
                </p>
            </div>

            {{-- Social Links --}}
            @if($creator->socialLinks->count())
                <div class="px-6 sm:px-10 pb-8">
                    <h2 class="text-center text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4">Find {{ Str::of($creator->name)->before(' ') }} On</h2>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        @foreach($creator->socialLinks as $link)
                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-primary hover:text-white transition-colors">
                                @include('creators.partials.social-icon', ['platform' => $link->platform, 'size' => 'w-4 h-4'])
                                {{ ucfirst($link->platform) }}
                                @if($link->handle)
                                    <span class="opacity-70">{{ '@' . $link->handle }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Claim Modal --}}
            @if($creator->status !== 'claimed')
                <div x-show="claimOpen" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                     @keydown.escape.window="claimOpen = false">
                    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" @click="claimOpen = false"></div>
                    <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10 overflow-hidden"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Claim this profile</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Help {{ $creator->name }} take ownership of their Topping Africa profile.
                                    </p>
                                </div>
                                <button type="button" @click="claimOpen = false" class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            @if(session('success'))
                                <div class="flex items-start gap-3 p-4 rounded-md bg-green-50 dark:bg-green-900/20 text-sm text-green-700 dark:text-green-400">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span>{{ session('success') }}</span>
                                </div>
                                <button type="button" @click="claimOpen = false"
                                        class="mt-2 w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    Close
                                </button>
                            @else
                                @php($recaptchaSiteKey = app(\App\Services\RecaptchaService::class)->getSiteKey())
                                <form action="{{ route('creators.request-claim', $creator->slug) }}" method="POST" class="space-y-3"
                                      x-data="{ siteKey: @js($recaptchaSiteKey), submitting: false, claimType: 'self' }"
                                      x-on:submit.prevent="
                                          if (submitting) return;
                                          submitting = true;
                                          const doSubmit = () => { $el.submit(); };
                                          if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                                              grecaptcha.enterprise.ready(async () => {
                                                  try {
                                                      const token = await grecaptcha.enterprise.execute(siteKey, { action: 'request_creator_claim' });
                                                      $refs.recaptchaToken.value = token;
                                                  } catch (e) {
                                                      $refs.recaptchaToken.value = 'RECAPTCHA_FAILED';
                                                  }
                                                  doSubmit();
                                              });
                                          } else if (siteKey) {
                                              $refs.recaptchaToken.value = 'RECAPTCHA_NOT_LOADED';
                                              doSubmit();
                                          } else {
                                              doSubmit();
                                          }
                                      ">
                                    @csrf
                                    <input type="hidden" name="recaptcha_token" x-ref="recaptchaToken" value="">
                                    <input type="hidden" name="claim_type" :value="claimType">

                                    {{-- Radio: Self vs Someone I know --}}
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="radio" value="self" x-model="claimType"
                                                   class="h-4 w-4 text-primary border-gray-300 dark:border-gray-600 focus:ring-primary">
                                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">This is me</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="radio" value="referral" x-model="claimType"
                                                   class="h-4 w-4 text-primary border-gray-300 dark:border-gray-600 focus:ring-primary">
                                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">I know this person</span>
                                        </label>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                            <span x-show="claimType === 'self'">Your email address</span>
                                            <span x-show="claimType === 'referral'" x-cloak>Their email address</span>
                                        </label>
                                        <input type="email" name="email" required
                                               :placeholder="claimType === 'self' ? 'Enter your email address' : 'Enter their email address'"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary focus:ring-primary text-sm">
                                    </div>
                                    @error('email')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('recaptcha')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <button type="submit" :disabled="submitting"
                                            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-primary text-white text-sm font-bold rounded-md hover:bg-primary-hover transition-colors disabled:opacity-60">
                                        <span x-show="!submitting && claimType === 'self'">Send claim link</span>
                                        <span x-show="!submitting && claimType === 'referral'" x-cloak>Send invite</span>
                                        <span x-show="submitting" x-cloak>Sending…</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- QR Card Modal --}}
            <div x-show="qrOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                 x-data="creatorQrCard({
                     slug: @js($creator->slug),
                     endpoint: @js(route('creators.qr-card', $creator->slug)),
                     trackEndpoint: @js(route('creators.qr-card.track', $creator->slug)),
                     csrf: @js(csrf_token()),
                     creatorName: @js($creator->name),
                 })"
                 x-init="$watch('qrOpen', v => v && load())"
                 @keydown.escape.window="qrOpen = false">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" @click="qrOpen = false"></div>
                <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10 overflow-hidden"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Share this creator</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Download a QR card. Scanning opens {{ $creator->name }}'s profile on any phone.
                                </p>
                            </div>
                            <button type="button" @click="qrOpen = false" class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Canvas preview --}}
                        <div class="relative rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 aspect-[4/5] flex items-center justify-center">
                            <template x-if="loading">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                    <span class="text-xs">Generating your card…</span>
                                </div>
                            </template>
                            <template x-if="error">
                                <p class="text-sm text-red-500 px-6 text-center" x-text="error"></p>
                            </template>
                            <canvas x-ref="cardCanvas" x-show="!loading && !error"
                                    class="w-full h-full object-contain"></canvas>
                        </div>

                        {{-- Primary action: native share (mobile) / copy image (desktop) --}}
                        <button type="button" x-show="canShare" @click="shareNative()" :disabled="loading || error || busy"
                                class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary to-secondary text-white text-sm font-bold rounded-md hover:opacity-90 transition disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>
                            <span x-text="busy ? 'Preparing…' : 'Share card'"></span>
                        </button>
                        <button type="button" x-show="!canShare && canCopy" @click="copyImage()" :disabled="loading || error || busy"
                                class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary to-secondary text-white text-sm font-bold rounded-md hover:opacity-90 transition disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z"/></svg>
                            <span x-text="busy ? 'Preparing…' : (copied ? 'Copied to clipboard!' : 'Copy image')"></span>
                        </button>

                        {{-- Secondary: download PNG/JPEG --}}
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <button type="button" @click="download('png')" :disabled="loading || error"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-sm font-bold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                PNG
                            </button>
                            <button type="button" @click="download('jpeg')" :disabled="loading || error"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-sm font-bold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                JPEG
                            </button>
                        </div>

                        <p class="mt-3 text-[11px] text-center text-gray-400 dark:text-gray-500">
                            Post to Instagram, X, WhatsApp — scanning opens {{ Str::of($creator->name)->before(' ') }}'s profile.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($featuredPosts) && $featuredPosts->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                Articles featuring {{ $creator->name }}
            </h2>

            {{-- Masonry grid (resources/js/creator-feed-masonry.js).
                 Item/sizer widths are set via a <style> block below so they don't depend on
                 Tailwind JIT extracting arbitrary calc() classes (which it silently dropped). --}}
            <style>
                .creator-feed-item { width: 100%; }
                @media (min-width: 640px) {
                    .creator-feed-item { width: calc((100% - 1.5rem) / 2); }
                }
                @media (min-width: 1024px) {
                    .creator-feed-item { width: calc((100% - 3rem) / 3); }
                }
            </style>

            <div data-creator-feed-masonry class="relative">
                <div data-masonry-sizer class="creator-feed-item"></div>

                @foreach($featuredPosts as $index => $post)
                    <div data-masonry-item class="creator-feed-item mb-6">
                        @include('blog.partials.post-card', ['post' => $post, 'showAuthor' => true, 'aspectAuto' => true])
                    </div>

                    @if($loop->iteration === 4 && ! $loop->last && \App\Models\Ad::active()->position('creator_feed')->exists())
                        <div data-masonry-item class="creator-feed-item mb-6">
                            <x-ad-slot position="creator_feed" />
                            <p class="mt-1 text-center text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500">Advertisement</p>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-8">
                {{ $featuredPosts->links() }}
            </div>
        </div>
    @endif
</section>

</x-layouts.blog>
