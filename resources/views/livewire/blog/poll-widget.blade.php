<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="p-6">
        {{-- Poll Header --}}
        <div class="flex items-center justify-between mb-5">
            <span class="text-xs font-semibold text-primary uppercase tracking-wide">Poll</span>
            @if($this->isExpired())
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                    Ended
                </span>
            @elseif($post->type_data['ends_at'] ?? null)
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    Ends {{ \Carbon\Carbon::parse($post->type_data['ends_at'])->diffForHumans() }}
                </span>
            @endif
        </div>

        @if($hasVoted || $this->isExpired() || (!empty($results) && ($post->type_data['show_results_before_vote'] ?? false)))
            {{-- Results View --}}
            <div class="space-y-3" x-data="{ shown: false }" x-init="$nextTick(() => shown = true)">
                @foreach($results as $index => $result)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-medium text-gray-900 dark:text-white {{ $hasVoted && $selectedOption === $index ? 'text-primary' : '' }}">
                                {{ $result['text'] }}
                                @if($hasVoted && $selectedOption === $index)
                                    <svg class="w-4 h-4 inline-block ml-1 text-primary" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                @endif
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ $result['percentage'] }}%</span>
                        </div>
                        <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 ease-out {{ $hasVoted && $selectedOption === $index ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-500' }}"
                                 x-bind:style="shown ? 'width: {{ $result['percentage'] }}%' : 'width: 0%'"></div>
                        </div>
                    </div>
                @endforeach

                <p class="text-xs text-gray-400 dark:text-gray-500 pt-2">
                    {{ $totalVotes }} {{ Str::plural('vote', $totalVotes) }}
                </p>
            </div>

            @if(!$hasVoted && !$this->isExpired() && ($post->type_data['show_results_before_vote'] ?? false))
                {{-- Show vote button even when showing results before vote --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    @guest
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                            <a href="{{ route('login') }}" class="text-primary hover:text-primary-hover font-semibold">Log in</a> to vote
                        </p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">Select an option above to vote</p>
                    @endguest
                </div>
            @endif
        @else
            {{-- Voting View --}}
            @guest
                <div class="space-y-2 mb-4">
                    @foreach($post->type_data['options'] ?? [] as $index => $option)
                        <div class="w-full text-left px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $option['text'] }}
                        </div>
                    @endforeach
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                    <a href="{{ route('login') }}" class="text-primary hover:text-primary-hover font-semibold">Log in</a> to vote
                </p>
            @else
                <div class="space-y-2 mb-4">
                    @foreach($post->type_data['options'] ?? [] as $index => $option)
                        <button wire:click="$set('selectedOption', {{ $index }})"
                                class="w-full text-left px-4 py-3 rounded-lg border-2 text-sm font-medium transition-all
                                {{ $selectedOption === $index
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            {{ $option['text'] }}
                        </button>
                    @endforeach
                </div>

                <button wire:click="vote"
                        :disabled="{{ $selectedOption === null ? 'true' : 'false' }}"
                        class="w-full py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ $selectedOption === null ? 'disabled' : '' }}>
                    Vote
                </button>
            @endguest
        @endif
    </div>
</div>
