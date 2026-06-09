<div class="space-y-6">
    <x-slot name="header">Content Calendar</x-slot>

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Content Calendar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                See what's been published, what's scheduled, and what's still in draft for any month at a glance.
            </p>
        </div>
        <a href="{{ route('admin.blog.posts.create') }}"
           class="inline-flex items-center gap-2 self-start rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 sm:self-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Post
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php
            $counts = $this->monthCounts;
            $cards = [
                ['label' => 'Total This Month', 'value' => $counts['total'], 'dot' => 'bg-gray-500'],
                ['label' => 'Published', 'value' => $counts['published'], 'dot' => 'bg-emerald-500'],
                ['label' => 'Scheduled', 'value' => $counts['scheduled'], 'dot' => 'bg-indigo-500'],
                ['label' => 'Drafts', 'value' => $counts['draft'], 'dot' => 'bg-amber-500'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full {{ $card['dot'] }}"></span>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ $card['label'] }}
                    </p>
                </div>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- AI Agent settings panel --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <button type="button" wire:click="toggleAgentSettings"
                    class="flex flex-1 items-center gap-3 text-left">
                <span @class([
                    'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $agentEnabled,
                    'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => ! $agentEnabled,
                ])>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/>
                    </svg>
                </span>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">AI Content Agent</h2>
                    @php $lastRun = $this->agentLastRun; @endphp
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($agentEnabled)
                            <span class="text-emerald-600 dark:text-emerald-400">Active</span> · runs daily at {{ $agentRunTime }} Lagos ·
                            @if ($agentPostsPerDayMin === $agentPostsPerDayMax)
                                {{ $agentPostsPerDayMin }} {{ \Illuminate\Support\Str::plural('post', $agentPostsPerDayMin) }}/day
                            @else
                                {{ $agentPostsPerDayMin }}–{{ $agentPostsPerDayMax }} posts/day
                            @endif
                        @else
                            <span class="text-gray-500 dark:text-gray-400">Disabled</span> · configure and enable to start auto-publishing
                        @endif
                        @if ($lastRun['has_run'] ?? false)
                            @php
                                $dispatched = (int) ($lastRun['dispatched'] ?? 0);
                                $failures = (int) ($lastRun['failures'] ?? 0);
                                $produced = max(0, $dispatched - $failures);
                            @endphp
                            <span class="mx-1">·</span>
                            <span>Last run {{ $lastRun['ran_at'] ? \Carbon\Carbon::parse($lastRun['ran_at'])->diffForHumans() : '—' }}:
                                {{ $produced }}/{{ $dispatched }} produced
                                @if (($lastRun['status'] ?? null) === 'no_ideas')
                                    <span class="text-amber-600 dark:text-amber-400">· no ideas available</span>
                                @endif
                                @if ($failures > 0)
                                    <span class="text-red-600 dark:text-red-400" title="{{ collect($lastRun['failed_titles'] ?? [])->implode("\n") }}">
                                        · {{ $failures }} {{ \Illuminate\Support\Str::plural('failure', $failures) }}
                                    </span>
                                @endif
                            </span>
                        @endif
                    </p>
                </div>
                <svg @class(['h-4 w-4 shrink-0 text-gray-400 transition-transform', 'rotate-180' => $showAgentSettings]) fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
            <div class="flex items-center gap-2 border-l border-gray-200 pl-3 dark:border-gray-700 sm:pl-3">
                <button type="button" wire:click="openDryRun"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Dry run
                </button>
                <button type="button" wire:click="openActivityFeed"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                    </svg>
                    Activity
                </button>
            </div>
        </div>

        @if ($showAgentSettings)
            <div class="border-t border-gray-200 p-5 dark:border-gray-700">
                <form wire:submit.prevent="saveAgentSettings" class="space-y-6">
                    {{-- Master toggle --}}
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" wire:model.live="agentEnabled"
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Enable AI Content Agent</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">When on, the agent picks ideas, generates posts, runs SEO, and schedules them automatically.</span>
                        </div>
                    </label>

                    {{-- Schedule & cadence --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Schedule &amp; Cadence</h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Min posts/day</span>
                                    <input type="number" min="1" max="10" wire:model="agentPostsPerDayMin"
                                           class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @error('agentPostsPerDayMin') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Max posts/day</span>
                                    <input type="number" min="1" max="10" wire:model="agentPostsPerDayMax"
                                           class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @error('agentPostsPerDayMax') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                                </label>
                            </div>

                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Daily run time (Lagos)</span>
                                <input type="time" wire:model="agentRunTime"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @error('agentRunTime') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Window start</span>
                                    <select wire:model="agentWindowStartHour"
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @for ($h = 0; $h < 24; $h++)
                                            <option value="{{ $h }}">{{ sprintf('%02d:00', $h) }}</option>
                                        @endfor
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Window end</span>
                                    <select wire:model="agentWindowEndHour"
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @for ($h = 1; $h < 24; $h++)
                                            <option value="{{ $h }}">{{ sprintf('%02d:00', $h) }}</option>
                                        @endfor
                                    </select>
                                </label>
                            </div>

                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Min gap between posts (hours)</span>
                                <input type="number" min="0.25" max="12" step="0.25" wire:model="agentMinGapHours"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </label>

                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Max gap between posts (hours)</span>
                                <input type="number" min="0.25" max="12" step="0.25" wire:model="agentMaxGapHours"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @error('agentMaxGapHours') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Posts are scheduled within the daily window with randomized gaps (between min/max) to feel human.
                        </p>
                    </div>

                    {{-- Quality bar --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Quality Bar</h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Minimum SEO score</span>
                                <input type="number" min="0" max="100" wire:model="agentMinSeoScore"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Posts below this score get auto-improved.</span>
                            </label>

                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Max improvement attempts</span>
                                <input type="number" min="1" max="5" wire:model="agentMaxImproveAttempts"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Each attempt re-runs SEO analysis + recommendations.</span>
                            </label>
                        </div>
                    </div>

                    {{-- Spotlights --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Creator Spotlights</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Spotlights feel special when they're rare. The agent counts spotlights published in the last 7 days and only boosts new ones into the daily batch while under the cap.
                        </p>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Max per 7 days</span>
                                <input type="number" min="0" max="14" wire:model="agentSpotlightWeeklyCap"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Set to 0 to disable spotlight auto-selection.</span>
                            </label>

                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Score boost while under cap</span>
                                <input type="number" min="0" max="50" wire:model="agentSpotlightScoreBoost"
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">How aggressively to favor spotlights vs. news articles. Reference: approved ideas get +30.</span>
                            </label>
                        </div>
                    </div>

                    {{-- Creator discovery --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Creator Discovery</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            When on, creators the AI references by name in a generated post but that aren't yet in your library are auto-created as <span class="font-medium">pending</span> and enriched in the background. Turn this off if discovery starts creating junk profiles.
                        </p>
                        <label class="mt-3 flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <input type="checkbox" wire:model.live="creatorDiscoveryEnabled"
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div class="flex-1">
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Auto-discover creators from generated posts</span>
                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">New profiles stay pending until an editor reviews them, so they never appear publicly without approval.</span>
                            </div>
                        </label>
                    </div>

                    {{-- Editorial guidance --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Editorial Guidance</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The agent reads these every run. Update them anytime to steer voice, topics, and focus.</p>

                        <label class="mt-3 block">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">House style &amp; instructions</span>
                            <textarea rows="4" wire:model="agentInstructions"
                                      placeholder="e.g. Focus on African tech startups, fashion designers, and music. Use a confident, energetic voice. Prefer second-person. Cite recent events when relevant."
                                      class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                            @error('agentInstructions') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Emphasize (one per line)</span>
                                <textarea rows="4" wire:model="agentEmphasizeTopics"
                                          placeholder="e.g.&#10;African tech startups&#10;Influencer culture&#10;Positive innovation"
                                          class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                            </label>

                            <label class="block">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Avoid (one per line)</span>
                                <textarea rows="4" wire:model="agentAvoidTopics"
                                          placeholder="e.g.&#10;Political conflicts&#10;Crime headlines&#10;Negative stereotypes"
                                          class="mt-1 block w-full rounded-md border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                            </label>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                        <button type="button" wire:click="resetAgentSettings"
                                class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Reset to defaults
                        </button>
                        <div class="flex items-center gap-3">
                            @if ($agentSettingsSaved)
                                <span class="text-xs text-emerald-600 dark:text-emerald-400">Saved</span>
                            @endif
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="saveAgentSettings"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-75">
                                <svg wire:loading wire:target="saveAgentSettings"
                                     class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="saveAgentSettings">Save agent settings</span>
                                <span wire:loading wire:target="saveAgentSettings">Saving...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>

    {{-- Toolbar: month nav + filters + legend --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="previousMonth"
                        class="rounded-md border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <button type="button" wire:click="goToToday"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Today
                </button>
                <button type="button" wire:click="nextMonth"
                        class="rounded-md border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
                <h2 class="ml-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $this->currentMonthLabel }}</h2>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="statusFilter"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">All statuses</option>
                    <option value="published">Published</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="draft">Draft</option>
                </select>

                <select wire:model.live="categoryId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All categories</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="authorId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All authors</option>
                    @foreach ($this->authors as $author)
                        <option value="{{ $author->id }}">{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-200 pt-3 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-400">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Published
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span> Scheduled
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Draft
            </span>
        </div>
    </div>

    {{-- Calendar grid --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow)
                <div class="py-2">{{ $dow }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach ($this->calendarDays as $day)
                @php
                    /** @var \Carbon\CarbonImmutable $date */
                    $date = $day['date'];
                    $inMonth = $day['in_month'];
                    $isToday = $day['is_today'];
                    $posts = $day['posts'];
                @endphp
                @php
                    $draftCap = \App\Livewire\Admin\Blog\ContentCalendar::DRAFTS_PER_CELL;
                    $primaryPosts = $posts->reject(fn ($p) => $p->status === 'draft')->values();
                    $draftPosts = $posts->filter(fn ($p) => $p->status === 'draft')->values();
                    $visibleDrafts = $draftPosts->take($draftCap);
                    $draftOverflow = max(0, $draftPosts->count() - $draftCap);
                @endphp
                <div @class([
                    'group relative min-h-36 border-b border-r border-gray-200 p-2 flex flex-col dark:border-gray-700',
                    'bg-white dark:bg-gray-800' => $inMonth,
                    'bg-gray-50 dark:bg-gray-900/40' => ! $inMonth,
                    'ring-2 ring-inset ring-indigo-500' => $isToday,
                ])
                    x-data="{ dragOver: false }"
                    :class="dragOver && 'bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-400'"
                    @dragover.prevent="dragOver = true"
                    @dragleave="dragOver = false"
                    @drop.prevent="
                        dragOver = false;
                        const id = $event.dataTransfer.getData('text/plain');
                        if (id) $wire.movePost(parseInt(id), '{{ $date->toDateString() }}');
                    ">
                    @if ($inMonth && $date->gte(\Carbon\CarbonImmutable::now()->startOfDay()))
                        <div class="pointer-events-none absolute right-1.5 top-1.5 opacity-0 transition-opacity group-hover:pointer-events-auto group-hover:opacity-100">
                            <button type="button" wire:click="openIdeaPicker('{{ $date->toDateString() }}')"
                                    class="peer flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white shadow-md hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                                    title="Generate post from a Content Lab idea">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </button>
                            <span class="pointer-events-none absolute right-8 top-0.5 hidden whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-[11px] font-medium text-white peer-hover:block dark:bg-gray-700">
                                Generate from idea
                            </span>
                        </div>
                    @endif

                    <div class="mb-1 flex items-center justify-between">
                        <span @class([
                            'text-xs font-semibold',
                            'text-gray-900 dark:text-gray-200' => $inMonth,
                            'text-gray-400 dark:text-gray-600' => ! $inMonth,
                            'text-indigo-600 dark:text-indigo-400' => $isToday,
                        ])>
                            {{ $date->day }}
                        </span>
                        @if ($posts->isNotEmpty())
                            <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 group-hover:opacity-0">{{ $posts->count() }}</span>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col gap-1">
                        @foreach ($primaryPosts as $post)
                            <button type="button" wire:click="openPost({{ $post->id }})"
                                    @if ($post->status === 'scheduled')
                                        draggable="true"
                                        @dragstart="$event.dataTransfer.setData('text/plain', '{{ $post->id }}'); $event.dataTransfer.effectAllowed = 'move';"
                                    @endif
                                    @class([
                                        'group block w-full shrink-0 rounded border-l-2 px-1.5 py-0.5 text-left transition-colors',
                                        'border-emerald-500 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30' => $post->status === 'published',
                                        'border-indigo-500 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/30 cursor-grab active:cursor-grabbing' => $post->status === 'scheduled',
                                    ])>
                                <div class="truncate text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ $post->title }}
                                </div>
                                <div class="mt-0.5 flex items-center gap-2 text-[10px] text-gray-500 dark:text-gray-400">
                                    <span class="capitalize">{{ $post->status }}</span>
                                    @if ($post->status === 'published')
                                        <span class="inline-flex items-center gap-0.5">
                                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ $post->views_count }}
                                        </span>
                                    @endif
                                </div>
                            </button>
                        @endforeach

                        @foreach ($visibleDrafts as $post)
                            <button type="button" wire:click="openPost({{ $post->id }})"
                                    draggable="true"
                                    @dragstart="$event.dataTransfer.setData('text/plain', '{{ $post->id }}'); $event.dataTransfer.effectAllowed = 'move';"
                                    class="group block w-full shrink-0 cursor-grab rounded border-l-2 border-amber-500 bg-amber-50 px-1.5 py-0.5 text-left transition-colors hover:bg-amber-100 active:cursor-grabbing dark:bg-amber-900/20 dark:hover:bg-amber-900/30">
                                <div class="truncate text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ $post->title }}
                                </div>
                                <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">Draft</div>
                            </button>
                        @endforeach

                        @if ($draftOverflow > 0)
                            <button type="button" wire:click="openDay('{{ $date->toDateString() }}')"
                                    class="mt-auto w-full rounded px-1.5 py-0.5 text-left text-[11px] font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/20">
                                + {{ $draftOverflow }} more {{ \Illuminate\Support\Str::plural('draft', $draftOverflow) }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Day detail modal --}}
    @if ($this->openDayDate)
        @php
            $dayPosts = $this->openDayPosts;
            $dayLabel = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $this->openDayDate)->translatedFormat('l, F j, Y');
        @endphp
        <div class="fixed inset-0 z-40 overflow-y-auto" wire:key="day-modal-{{ $this->openDayDate }}">
            <div class="fixed inset-0 bg-gray-900/70 transition-opacity" wire:click="closeDay"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl rounded-lg bg-white shadow-xl dark:bg-gray-800"
                     x-data
                     @keydown.escape.window="$wire.closeDay()">

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $dayLabel }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $dayPosts->count() }} {{ \Illuminate\Support\Str::plural('post', $dayPosts->count()) }}</p>
                        </div>
                        <button type="button" wire:click="closeDay"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto px-3 py-3">
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($dayPosts as $post)
                                <li>
                                    <button type="button" wire:click="openPost({{ $post->id }})"
                                            class="flex w-full items-start gap-3 rounded-md px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <span @class([
                                            'mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full',
                                            'bg-emerald-500' => $post->status === 'published',
                                            'bg-indigo-500' => $post->status === 'scheduled',
                                            'bg-amber-500' => $post->status === 'draft',
                                        ])></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $post->title }}</p>
                                            <div class="mt-0.5 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="capitalize">{{ $post->status }}</span>
                                                @if ($post->author)
                                                    <span>·</span>
                                                    <span>{{ $post->author->name }}</span>
                                                @endif
                                                @if ($post->status === 'published')
                                                    <span>·</span>
                                                    <span>{{ number_format($post->views_count) }} views</span>
                                                @endif
                                            </div>
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Idea picker modal --}}
    @if ($this->ideaPickerDate)
        @php
            $pickerLabel = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $this->ideaPickerDate)->translatedFormat('l, F j, Y');
            $ideas = $this->pickerIdeas;
        @endphp
        <div class="fixed inset-0 z-40 overflow-y-auto" wire:key="idea-picker-{{ $this->ideaPickerDate }}">
            <div class="fixed inset-0 bg-gray-900/70 transition-opacity" wire:click="closeIdeaPicker"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-800"
                     x-data
                     @keydown.escape.window="$wire.closeIdeaPicker()">

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Generate post from Content Lab</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Recent ideas and high-ranking suggestions from the Content Lab.</p>
                        </div>
                        <button type="button" wire:click="closeIdeaPicker"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="border-b border-gray-200 px-6 py-3 dark:border-gray-700">
                        <input type="search" wire:model.live.debounce.300ms="ideaSearch" placeholder="Search ideas..."
                               class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500">
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto px-3 py-2">
                        @if ($ideas->isEmpty())
                            <div class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($this->ideaSearch !== '')
                                    No ideas match "{{ $this->ideaSearch }}".
                                @else
                                    No ideas in the Content Lab yet.
                                    <a href="{{ route('admin.blog.content-lab') }}" class="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                        Open Content Lab
                                    </a>
                                @endif
                            </div>
                        @else
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($ideas as $idea)
                                    <li class="group/idea flex items-start gap-3 rounded-md px-3 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                @php
                                                    $badgeClasses = match ($idea->status) {
                                                        'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                        'pending' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                        default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wider {{ $badgeClasses }}">
                                                    {{ $idea->status }}
                                                </span>
                                                @if ($idea->expires_at && $idea->expires_at->isPast() && $idea->status === 'pending')
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wider text-red-700 dark:bg-red-900/40 dark:text-red-300">Expired</span>
                                                @endif
                                                @if ($idea->niche)
                                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $idea->niche }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $idea->title }}</p>
                                            @if ($idea->suggested_keyword)
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="font-medium">Keyword:</span> {{ $idea->suggested_keyword }}
                                                </p>
                                            @endif
                                            <div class="mt-1.5 flex flex-wrap items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400">
                                                @if ($idea->seo_score)
                                                    <span class="inline-flex items-center gap-1">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
                                                        </svg>
                                                        SEO {{ $idea->seo_score }}
                                                    </span>
                                                @endif
                                                @if ($idea->relevance_score)
                                                    <span>Relevance {{ $idea->relevance_score }}</span>
                                                @endif
                                                @if ($idea->suggested_post_type)
                                                    <span class="capitalize">{{ $idea->suggested_post_type }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button"
                                                wire:click="generateFromIdea({{ $idea->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="generateFromIdea({{ $idea->id }})"
                                                class="shrink-0 inline-flex items-center gap-1 rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                                            <span wire:loading.remove wire:target="generateFromIdea({{ $idea->id }})">
                                                Generate
                                            </span>
                                            <span wire:loading wire:target="generateFromIdea({{ $idea->id }})">
                                                Generating...
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                        <span>Showing {{ $ideas->count() }} recent &amp; high-ranking {{ \Illuminate\Support\Str::plural('idea', $ideas->count()) }}</span>
                        <a href="{{ route('admin.blog.content-lab') }}" class="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                            Manage in Content Lab →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Post detail modal --}}
    @if ($post = $this->selectedPost)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="post-modal-{{ $post->id }}">
            <div class="fixed inset-0 bg-gray-900/70 transition-opacity" wire:click="closePost"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-800"
                     x-data
                     @keydown.escape.window="$wire.closePost()">

                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex-1">
                            @php
                                $statusBadge = match ($post->status) {
                                    'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'scheduled' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                    default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                };
                                $statusDate = match ($post->status) {
                                    'published' => $post->published_at?->format('M j, Y g:i A'),
                                    'scheduled' => $post->scheduled_at?->format('M j, Y g:i A'),
                                    default => $post->updated_at?->format('M j, Y g:i A'),
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $statusBadge }}">
                                {{ $post->status }}
                                @if ($statusDate)
                                    <span class="ml-1.5 opacity-75">· {{ $statusDate }}</span>
                                @endif
                            </span>
                            <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $post->title }}
                            </h3>
                            @if ($post->author)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">By {{ $post->author->name }}</p>
                            @endif
                        </div>
                        <button type="button" wire:click="closePost"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="space-y-5 px-6 py-5">
                        @if ($post->excerpt)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $post->excerpt }}</p>
                        @endif

                        {{-- Categories --}}
                        @if ($post->categories->isNotEmpty())
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Categories</h4>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($post->categories as $category)
                                        <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $category->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Keywords / tags --}}
                        @php
                            $keywords = collect()
                                ->when($post->focus_keyword, fn ($c) => $c->push(['label' => $post->focus_keyword, 'focus' => true]))
                                ->merge($post->tags->map(fn ($tag) => ['label' => $tag->name, 'focus' => false]));
                        @endphp
                        @if ($keywords->isNotEmpty())
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Keywords</h4>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($keywords as $kw)
                                        <span @class([
                                            'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
                                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => $kw['focus'],
                                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => ! $kw['focus'],
                                        ])>
                                            @if ($kw['focus'])
                                                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endif
                                            {{ $kw['label'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- SEO ranking --}}
                        @if ($seo = $post->latestSeoAnalysis)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">SEO Score</h4>
                                <div class="mt-2 flex items-center gap-4">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $seo->overall_score }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">/ 100</span>
                                    </div>
                                    @if ($seo->grade)
                                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-sm font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            {{ $seo->grade }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Traffic (published only) --}}
                        @if ($post->status === 'published')
                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-lg border border-gray-200 p-3 text-center dark:border-gray-700">
                                    <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Views</p>
                                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($post->views_count) }}</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 p-3 text-center dark:border-gray-700">
                                    <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Reactions</p>
                                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($post->reactions_count) }}</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 p-3 text-center dark:border-gray-700">
                                    <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Comments</p>
                                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($post->comments_count) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                        @if ($post->status === 'published')
                            <a href="{{ url('/'.$post->slug) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                </svg>
                                View Post
                            </a>
                        @endif
                        <a href="{{ route('admin.blog.posts.edit', $post->id) }}"
                           class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                            Edit Post
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Dry-run preview modal --}}
    @if ($showDryRun)
        @php $preview = $this->dryRunPreview; @endphp
        <div class="fixed inset-0 z-40 overflow-y-auto" wire:key="dry-run-modal">
            <div class="fixed inset-0 bg-gray-900/70 transition-opacity" wire:click="closeDryRun"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl rounded-lg bg-white shadow-xl dark:bg-gray-800"
                     x-data
                     @keydown.escape.window="$wire.closeDryRun()">

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Dry run preview</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Shows what the agent would do if it ran right now. No posts are generated.
                            </p>
                        </div>
                        <button type="button" wire:click="closeDryRun"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                        @if ($preview['ideas']->isEmpty())
                            <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
                                <strong>No eligible ideas right now.</strong> The agent would log a "no_ideas" run and exit.
                                Open <a href="{{ route('admin.blog.content-lab') }}" class="font-medium underline">Content Lab</a> to seed more.
                            </div>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                The agent would generate {{ $preview['ideas']->count() }} {{ \Illuminate\Support\Str::plural('post', $preview['ideas']->count()) }} and schedule them at the slots below.
                            </p>
                            <ul class="mt-3 space-y-2">
                                @foreach ($preview['ideas'] as $index => $idea)
                                    @php $slot = $preview['slots'][$index] ?? null; @endphp
                                    <li class="flex items-start gap-3 rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $idea->title }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                <span class="capitalize">{{ $idea->status }}</span>
                                                @if ($idea->seo_score)
                                                    <span>SEO {{ $idea->seo_score }}</span>
                                                @endif
                                                @if ($idea->niche)
                                                    <span>{{ $idea->niche }}</span>
                                                @endif
                                                @if ($slot)
                                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">
                                                        → {{ $slot->format('D, M j · g:i A') }} Lagos
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-5 rounded-md bg-gray-50 p-3 text-xs text-gray-600 dark:bg-gray-900/40 dark:text-gray-400">
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                <div><span class="font-semibold">Posts/day:</span>
                                    @if ($preview['config']['posts_per_day_min'] === $preview['config']['posts_per_day_max'])
                                        {{ $preview['config']['posts_per_day_min'] }}
                                    @else
                                        {{ $preview['rolled_count'] ?? $preview['config']['posts_per_day_min'] }} of {{ $preview['config']['posts_per_day_min'] }}–{{ $preview['config']['posts_per_day_max'] }}
                                    @endif
                                </div>
                                <div><span class="font-semibold">Window:</span> {{ sprintf('%02d:00', $preview['config']['window_start']) }} – {{ sprintf('%02d:00', $preview['config']['window_end']) }}</div>
                                <div><span class="font-semibold">Gap:</span> {{ $preview['config']['min_gap'] }}–{{ $preview['config']['max_gap'] }}h</div>
                                <div><span class="font-semibold">SEO min:</span> {{ $preview['config']['min_seo_score'] }}</div>
                                <div title="Remaining spotlight slots in the rolling 7-day cap">
                                    <span class="font-semibold">Spotlights:</span>
                                    {{ $preview['spotlight_quota_remaining'] ?? 0 }} of {{ $preview['config']['spotlight_weekly_cap'] }} left
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                        <button type="button" wire:click="closeDryRun"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Activity feed modal --}}
    @if ($showActivityFeed)
        @php $activity = $this->agentActivity; @endphp
        <div class="fixed inset-0 z-40 overflow-y-auto" wire:key="activity-modal">
            <div class="fixed inset-0 bg-gray-900/70 transition-opacity" wire:click="closeActivityFeed"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-800"
                     x-data
                     @keydown.escape.window="$wire.closeActivityFeed()">

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Agent activity</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Most recent 30 events the agent logged.</p>
                        </div>
                        <button type="button" wire:click="closeActivityFeed"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto px-3 py-2">
                        @if ($activity->isEmpty())
                            <div class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                The agent hasn't logged anything yet. Once it runs, events will appear here.
                            </div>
                        @else
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($activity as $entry)
                                    @php
                                        $props = $entry->properties ?? collect();
                                        $event = $entry->event ?? 'event';
                                        $dotClass = match ($event) {
                                            'post_scheduled' => 'bg-emerald-500',
                                            'daily_run' => 'bg-indigo-500',
                                            'generation_failed' => 'bg-red-500',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <li class="flex items-start gap-3 px-3 py-3">
                                        <span class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full {{ $dotClass }}"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $entry->description }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                <span>{{ $entry->created_at->diffForHumans() }}</span>
                                                <span class="capitalize">{{ str_replace('_', ' ', $event) }}</span>
                                                @if ($props->get('seo_score'))
                                                    <span>SEO {{ $props->get('seo_score') }}</span>
                                                @endif
                                                @if ($props->get('scheduled_at'))
                                                    <span>→ {{ \Carbon\Carbon::parse($props->get('scheduled_at'))->format('M j · g:i A') }}</span>
                                                @endif
                                                @if ($props->get('error'))
                                                    <span class="text-red-600 dark:text-red-400">{{ $props->get('error') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
