{{--
    Unified frontend dashboard sidebar — role-aware menu.

    Used by every page under /dashboard. Sits inside the existing blog
    layout (top header and footer unchanged), rendered as a left column
    next to the main content area.

    Menu items are gated by Spatie role via $user->hasRole(). A regular
    user sees the MAIN + ACCOUNT sections. A creator additionally sees
    the CREATOR section with their profiles, articles (soon), and perks
    (soon). Future roles (author, editor) will get their own sections
    when we build those surfaces.

    Logout is a form POST to the existing /logout route.
--}}
@php($user = auth()->user())
<aside class="w-full sm:w-56 flex-shrink-0">
    <nav class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">

        {{-- MAIN section --}}
        <ul class="space-y-1">
            <li>
                <a href="{{ route('dashboard') }}"
                   class="block px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard')
                             ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                             : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                    Dashboard
                </a>
            </li>
        </ul>

        {{-- CREATOR section — only for users with the creator role.
             "My Profiles" is intentionally not here — the Dashboard link
             above already takes creators to their profile cards. --}}
        @if($user->hasRole('creator'))
            <div class="mt-4 mb-2 px-3 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Creator
            </div>
            <ul class="space-y-1">
                <li>
                    <a href="#" title="Coming soon"
                       class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Articles
                        <span class="ml-1 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">soon</span>
                    </a>
                </li>
                <li>
                    <a href="#" title="Coming soon"
                       class="block px-3 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Perks
                        <span class="ml-1 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">soon</span>
                    </a>
                </li>
            </ul>
        @endif

        {{-- ACCOUNT section (divider + items at the bottom, for everyone) --}}
        <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>
        <ul class="space-y-1">
            <li>
                <a href="{{ route('dashboard.settings') }}"
                   class="block px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard.settings')
                             ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                             : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                    Settings
                </a>
            </li>
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full text-left block px-3 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Log out
                    </button>
                </form>
            </li>
        </ul>

    </nav>
</aside>
