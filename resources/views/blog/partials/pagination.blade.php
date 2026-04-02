@if ($paginator->hasPages())
    <nav class="flex justify-center">
        <ul class="inline-flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li><span class="px-3 py-2 text-sm text-gray-300 dark:text-gray-600 cursor-not-allowed">&laquo;</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">&laquo;</a></li>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-3 py-2 text-sm text-gray-400">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold text-white bg-primary rounded-full">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="inline-flex items-center justify-center w-10 h-10 text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">&raquo;</a></li>
            @else
                <li><span class="px-3 py-2 text-sm text-gray-300 dark:text-gray-600 cursor-not-allowed">&raquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
