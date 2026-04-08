<x-layouts.blog
    title="Settings"
    :metaDescription="'Manage your Topping Africa account settings.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row gap-6">
        {{-- Left sidebar --}}
        @include('dashboard.partials.sidebar')

        {{-- Main content --}}
        <div class="flex-1 min-w-0">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Settings</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage your account information, password, and data.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-5 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Breeze's three existing account forms — unchanged, just
                 rehoused in the dashboard layout. --}}
            <div class="max-w-2xl space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                    <livewire:profile.update-profile-information-form />
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                    <livewire:profile.update-password-form />
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>{{-- end main content --}}
    </div>{{-- end flex row --}}
</section>

</x-layouts.blog>
