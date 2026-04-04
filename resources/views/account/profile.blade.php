<x-layouts.blog title="Account Settings">
    <div class="max-w-container mx-auto px-4 py-10">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-8">
            <a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">My Account</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="text-gray-900 dark:text-white font-medium">Account Settings</span>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Account Settings</h1>

        <div class="max-w-2xl space-y-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <livewire:profile.update-password-form />
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-layouts.blog>
