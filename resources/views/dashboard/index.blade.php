<x-layouts.blog
    title="Dashboard"
    :metaDescription="'Your Topping Africa dashboard.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row gap-6">
        {{-- Left sidebar --}}
        @include('dashboard.partials.sidebar')

        {{-- Main content --}}
        <div class="flex-1 min-w-0">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                    Welcome, {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }}
                </h1>
            </div>

            @if(session('success'))
                <div class="mb-5 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Role-branched content:
                 - Creators see their claimed profile cards.
                 - Everyone else sees their Recently Viewed + Comments history. --}}
            @if($user->hasRole('creator'))

                {{-- Creator content: profile cards --}}
                @if($creators->isEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">You haven't claimed any creator profiles yet.</p>
                        <a href="{{ template_url('creators') }}" class="mt-3 inline-block text-sm font-semibold text-primary hover:underline">
                            Browse creators →
                        </a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($creators as $creator)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                                <div class="flex items-start gap-4">
                                    {{-- Avatar --}}
                                    <div class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0">
                                        @if($creator->profile_image_url)
                                            <img src="{{ $creator->profile_image_url }}" alt="{{ $creator->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold"
                                                 style="background-color: {{ $creator->avatar_color }}">
                                                {{ $creator->initials }}
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $creator->name }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $creator->category }} &middot; {{ $creator->country }}
                                        </p>

                                        {{-- Stats row: followers · views · updated --}}
                                        @php
                                            $stats = [];
                                            if ($followerShort = \App\Support\FollowerFormat::short($creator->follower_count)) {
                                                $stats[] = $followerShort . ' followers';
                                            }
                                            $viewsShort = \App\Support\FollowerFormat::short($creator->views_count) ?? '0';
                                            $stats[] = $viewsShort . ' ' . \Illuminate\Support\Str::plural('view', $creator->views_count);
                                            $stats[] = 'Updated ' . $creator->updated_at->diffForHumans(null, true) . ' ago';
                                        @endphp
                                        <p class="mt-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                                            {{ implode(' · ', $stats) }}
                                        </p>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex flex-col gap-2 flex-shrink-0">
                                        <a href="{{ route('creators.claim.edit-as-owner', ['creatorId' => $creator->id]) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-md hover:bg-primary-hover transition-colors">
                                            Edit
                                        </a>
                                        <a href="{{ route('creators.show', $creator->slug) }}" target="_blank"
                                           class="inline-flex items-center justify-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Claim another --}}
                    <div class="mt-6 text-center">
                        <a href="{{ template_url('creators') }}" class="text-sm font-semibold text-primary hover:underline">
                            Browse creators to claim another profile →
                        </a>
                    </div>
                @endif

            @else

                {{-- Submit creator profile CTA --}}
                @unless(auth()->user()->claimedCreators()->exists())
                    @php $isVerified = auth()->user()->hasVerifiedEmail(); @endphp
                    <div class="bg-primary/5 dark:bg-primary/10 rounded-xl border border-primary/20 p-5 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">Are you a creator?</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Submit your profile to be featured on Topping Africa and reach a wider audience.</p>
                                @if($isVerified)
                                    <a href="{{ route('creators.submit') }}"
                                       class="inline-flex items-center gap-1 mt-3 text-sm font-semibold text-primary hover:text-primary-hover transition-colors">
                                        Submit Your Profile
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                    </a>
                                @else
                                    <p class="inline-flex items-center gap-1 mt-3 text-sm font-semibold text-gray-400 dark:text-gray-500 cursor-not-allowed" title="Verify your email to submit your profile">
                                        Verify your email to continue
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endunless

                {{-- Non-creator content: inline Recently Viewed + Comments.
                     Reuses the same Livewire components that power the
                     /my-account page so load-more pagination is free. --}}
                <div class="space-y-8">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">
                            Recently Viewed
                        </h2>
                        <livewire:account.reading-history />
                    </div>

                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">
                            My Comments
                        </h2>
                        <livewire:account.my-comments />
                    </div>
                </div>

            @endif
        </div>{{-- end main content --}}
    </div>{{-- end flex row --}}
</section>

</x-layouts.blog>
