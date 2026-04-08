<x-layouts.blog
    title="Creator Dashboard"
    :metaDescription="'Manage your Topping Africa creator profiles.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row gap-6">
        {{-- Left sidebar --}}
        @include('creators.partials.dashboard-sidebar')

        {{-- Main content --}}
        <div class="flex-1 min-w-0">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Welcome, {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($creators->count() === 1)
                        Manage your creator profile.
                    @else
                        Manage your {{ $creators->count() }} creator profiles.
                    @endif
                </p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    Log out
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Profiles list --}}
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
        </div>{{-- end main content column --}}
    </div>{{-- end flex row --}}
</section>

</x-layouts.blog>
