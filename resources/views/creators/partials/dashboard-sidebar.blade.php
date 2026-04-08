{{--
    Left sidebar nav for the creator dashboard area.

    Sits inside the existing blog layout's main content area — this is
    NOT a chrome/shell change. Same top header, same footer, same site.
    The sidebar is just a left column added next to the dashboard content.

    Most links currently point to "#" because the destination pages don't
    exist yet — clicking them shows a "Coming Soon" tooltip via the title
    attribute. The Dashboard link points to the current page, and Log out
    submits the existing logout form.
--}}
<aside class="w-full sm:w-56 flex-shrink-0">
    <nav class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
        @php
            // Top group
            $topItems = [
                ['label' => 'Dashboard', 'href' => route('creator.dashboard'), 'active' => true,  'soon' => false],
                ['label' => 'Articles',  'href' => '#',                          'active' => false, 'soon' => true],
                ['label' => 'Perks',     'href' => '#',                          'active' => false, 'soon' => true],
            ];
            // Bottom group
            $bottomItems = [
                ['label' => 'My Profile', 'href' => '#', 'active' => false, 'soon' => true],
                ['label' => 'Account',    'href' => '#', 'active' => false, 'soon' => true],
            ];
        @endphp

        <ul class="space-y-1">
            @foreach($topItems as $item)
                <li>
                    <a href="{{ $item['href'] }}"
                       @if($item['soon']) title="Coming soon" @endif
                       class="block px-3 py-2 rounded-md text-sm font-medium transition-colors
                              {{ $item['active']
                                  ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white' }}">
                        {{ $item['label'] }}
                        @if($item['soon'])
                            <span class="ml-1 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">soon</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

        <ul class="space-y-1">
            @foreach($bottomItems as $item)
                <li>
                    <a href="{{ $item['href'] }}"
                       @if($item['soon']) title="Coming soon" @endif
                       class="block px-3 py-2 rounded-md text-sm font-medium transition-colors
                              text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white">
                        {{ $item['label'] }}
                        @if($item['soon'])
                            <span class="ml-1 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">soon</span>
                        @endif
                    </a>
                </li>
            @endforeach

            {{-- Logout (form, not a link, so it can POST to /logout) --}}
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
