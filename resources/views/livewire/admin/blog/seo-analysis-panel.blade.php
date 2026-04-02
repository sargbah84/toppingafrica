<div>
    <!-- SEO Analysis Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="{ seoAnalysisExpanded: true }">
        <button type="button" @click="seoAnalysisExpanded = !seoAnalysisExpanded"
            class="w-full flex items-center justify-between px-4 lg:px-6 py-4 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-indigo-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span class="font-medium text-gray-900 dark:text-white">SEO Analysis</span>
                @if($analysis)
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold
                        {{ in_array($analysis['grade'] ?? '', ['A+', 'A']) ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : '' }}
                        {{ in_array($analysis['grade'] ?? '', ['B+', 'B']) ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : '' }}
                        {{ in_array($analysis['grade'] ?? '', ['C+', 'C']) ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : '' }}
                        {{ in_array($analysis['grade'] ?? '', ['D', 'F']) ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : '' }}">
                        {{ $analysis['grade'] ?? 'N/A' }} ({{ $analysis['overall_score'] ?? 0 }}/100)
                    </span>
                @else
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">Not Analyzed</span>
                @endif
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="seoAnalysisExpanded ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div x-show="seoAnalysisExpanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="p-4 lg:p-6 space-y-5 border-t border-gray-200 dark:border-gray-700">

                @if($analysis)
                    <!-- Score Overview -->
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0 w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-2xl
                            {{ in_array($analysis['grade'], ['A+', 'A']) ? 'bg-green-500' : '' }}
                            {{ in_array($analysis['grade'], ['B+', 'B']) ? 'bg-blue-500' : '' }}
                            {{ in_array($analysis['grade'], ['C+', 'C']) ? 'bg-amber-500' : '' }}
                            {{ in_array($analysis['grade'], ['D', 'F']) ? 'bg-red-500' : '' }}">
                            {{ $analysis['grade'] }}
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $analysis['overall_score'] }}/100</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Last analyzed: {{ \Carbon\Carbon::parse($analysis['created_at'])->diffForHumans() }}</div>
                        </div>
                    </div>

                    <!-- Category Progress Bars -->
                    <div class="space-y-3">
                        @php
                            $categories = [
                                ['key' => 'content_quality_score', 'label' => 'Content Quality', 'weight' => '30%'],
                                ['key' => 'technical_seo_score', 'label' => 'Technical SEO', 'weight' => '25%'],
                                ['key' => 'readability_score', 'label' => 'Readability', 'weight' => '20%'],
                                ['key' => 'user_engagement_score', 'label' => 'User Engagement', 'weight' => '15%'],
                                ['key' => 'on_page_elements_score', 'label' => 'On-Page Elements', 'weight' => '10%'],
                            ];
                        @endphp

                        @foreach($categories as $cat)
                            @php
                                $catScore = $analysis[$cat['key']] ?? 0;
                                $barColor = $catScore >= 80 ? 'bg-green-500' : ($catScore >= 60 ? 'bg-blue-500' : ($catScore >= 40 ? 'bg-amber-500' : 'bg-red-500'));
                            @endphp
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $cat['label'] }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $catScore }}/100</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="{{ $barColor }} h-2 rounded-full transition-all duration-700"
                                        style="width: {{ $catScore }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- AI Feedback Tabs -->
                    @if(!empty($analysis['strengths']) || !empty($analysis['improvements']) || !empty($analysis['critical_issues']))
                        <div class="mt-6">
                            <div class="flex border-b border-gray-200 dark:border-gray-700">
                                <button type="button" wire:click="setActiveTab('strengths')"
                                    class="px-4 py-2 text-sm font-medium border-b-2 transition
                                        {{ $activeTab === 'strengths' ? 'border-green-500 text-green-700 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                                    <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Strengths
                                </button>
                                <button type="button" wire:click="setActiveTab('improvements')"
                                    class="px-4 py-2 text-sm font-medium border-b-2 transition
                                        {{ $activeTab === 'improvements' ? 'border-amber-500 text-amber-700 dark:text-amber-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                                    <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                    </svg>
                                    Improvements
                                </button>
                                @if(!empty($analysis['critical_issues']))
                                    <button type="button" wire:click="setActiveTab('critical')"
                                        class="px-4 py-2 text-sm font-medium border-b-2 transition
                                            {{ $activeTab === 'critical' ? 'border-red-500 text-red-700 dark:text-red-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                                        <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                        </svg>
                                        Critical
                                    </button>
                                @endif
                            </div>

                            <div class="py-3">
                                @if($activeTab === 'strengths' && !empty($analysis['strengths']))
                                    <ul class="space-y-2">
                                        @foreach($analysis['strengths'] as $strength)
                                            <li class="flex items-start gap-2 text-sm">
                                                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                <span class="text-gray-700 dark:text-gray-300">{{ $strength }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if($activeTab === 'improvements' && !empty($analysis['improvements']))
                                    <ol class="space-y-2">
                                        @foreach($analysis['improvements'] as $improvement)
                                            <li class="flex items-start gap-2 text-sm">
                                                <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                                </svg>
                                                <span class="text-gray-700 dark:text-gray-300">{{ $improvement }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif

                                @if($activeTab === 'critical' && !empty($analysis['critical_issues']))
                                    <ul class="space-y-2">
                                        @foreach($analysis['critical_issues'] as $issue)
                                            <li class="flex items-start gap-2 text-sm">
                                                <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                <span class="text-gray-700 dark:text-gray-300">{{ $issue }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="analyze"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium disabled:opacity-50"
                            @if($isAnalyzing) disabled @endif>
                            <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                            </svg>
                            Re-analyze
                        </button>

                        @if(($analysis['overall_score'] ?? 100) < 90)
                            <button type="button" wire:click="openApplyPreview"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                                <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                                </svg>
                                Apply Recommendations
                            </button>
                        @endif

                        <button type="button" wire:click="openFullReport"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm font-medium">
                            <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            Full Report
                        </button>
                    </div>
                @else
                    <!-- No Analysis Yet -->
                    <div class="text-center py-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">No SEO analysis yet</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Analyze your post to get SEO insights and recommendations</p>

                        <button type="button" wire:click="analyze"
                            class="mt-4 px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium disabled:opacity-50"
                            @if(!$postId || $isAnalyzing) disabled @endif
                            @if(!$postId) title="Save the post first" @endif>
                            <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            Analyze SEO
                        </button>

                        @if(!$postId)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Save the post first to enable SEO analysis</p>
                        @endif
                    </div>
                @endif

                <!-- Error Message -->
                @if($errorMessage)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mt-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <span class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</span>
                        </div>
                    </div>
                @endif

                <!-- Success Message -->
                @if($successMessage)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 mt-3"
                        x-data="{ show: true }"
                        x-show="show"
                        x-init="setTimeout(() => show = false, 5000)"
                        x-transition>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="text-sm text-green-700 dark:text-green-400">{{ $successMessage }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Full Report Modal -->
    @if($showFullReport && $analysis)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeFullReport()">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SEO Full Report</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-sm font-bold
                            {{ in_array($analysis['grade'], ['A+', 'A']) ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : '' }}
                            {{ in_array($analysis['grade'], ['B+', 'B']) ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : '' }}
                            {{ in_array($analysis['grade'], ['C+', 'C']) ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : '' }}
                            {{ in_array($analysis['grade'], ['D', 'F']) ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : '' }}">
                            {{ $analysis['grade'] }} ({{ $analysis['overall_score'] }}/100)
                        </span>
                    </div>
                    <button type="button" wire:click="closeFullReport" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto" style="max-height: calc(85vh - 80px);">
                    <!-- Category Breakdown -->
                    <div class="space-y-6">
                        @php
                            $detailCategories = [
                                ['key' => 'content_quality', 'label' => 'Content Quality', 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z', 'score_key' => 'content_quality_score'],
                                ['key' => 'technical_seo', 'label' => 'Technical SEO', 'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.653-4.657m5.014-2.024a3 3 0 0 0-.752-4.543 3 3 0 0 0-4.135.765M11.42 15.17l.394-.478', 'score_key' => 'technical_seo_score'],
                                ['key' => 'readability', 'label' => 'Readability', 'icon' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25', 'score_key' => 'readability_score'],
                                ['key' => 'user_engagement', 'label' => 'User Engagement', 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z', 'score_key' => 'user_engagement_score'],
                                ['key' => 'on_page_elements', 'label' => 'On-Page Elements', 'icon' => 'M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z', 'score_key' => 'on_page_elements_score'],
                            ];
                        @endphp

                        @foreach($detailCategories as $dc)
                            @php
                                $dcScore = $analysis[$dc['score_key']] ?? 0;
                                $dcDetails = $analysis[$dc['key'] . '_details'] ?? [];
                                $dcBarColor = $dcScore >= 80 ? 'bg-green-500' : ($dcScore >= 60 ? 'bg-blue-500' : ($dcScore >= 40 ? 'bg-amber-500' : 'bg-red-500'));
                            @endphp
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-900">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $dc['icon'] }}" />
                                        </svg>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $dc['label'] }}</span>
                                    </div>
                                    <span class="text-sm font-bold {{ $dcScore >= 80 ? 'text-green-600 dark:text-green-400' : ($dcScore >= 60 ? 'text-blue-600 dark:text-blue-400' : ($dcScore >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400')) }}">
                                        {{ $dcScore }}/100
                                    </span>
                                </div>
                                <div class="px-4 py-3">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-3">
                                        <div class="{{ $dcBarColor }} h-2 rounded-full" style="width: {{ $dcScore }}%"></div>
                                    </div>

                                    @if(!empty($dcDetails))
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            @foreach($dcDetails as $detailKey => $detailValue)
                                                @if(!in_array($detailKey, ['score', 'sub_scores']) && !is_array($detailValue))
                                                    <div class="flex justify-between col-span-2 sm:col-span-1">
                                                        <span class="text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($detailKey)) }}:</span>
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                                            @if(is_bool($detailValue))
                                                                @if($detailKey === 'keyword_stuffing_detected')
                                                                    {!! $detailValue ? '<span class="text-red-600 dark:text-red-400 font-bold">Detected</span>' : '<span class="text-green-600 dark:text-green-400">OK</span>' !!}
                                                                @else
                                                                    {!! $detailValue ? '<span class="text-green-600 dark:text-green-400">Yes</span>' : '<span class="text-red-500 dark:text-red-400">No</span>' !!}
                                                                @endif
                                                            @elseif($detailKey === 'keyword_density')
                                                                {{ $detailValue }}% <span class="text-xs text-gray-400 dark:text-gray-500">(target: 1-2%)</span>
                                                            @else
                                                                {{ $detailValue }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Keyword Suggestions -->
                    @if(!empty($analysis['keyword_suggestions']))
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Keyword Suggestions</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($analysis['keyword_suggestions'] as $kwIndex => $keyword)
                                    @php
                                        $isApplied = in_array(strtolower($keyword), $postTags);
                                        $kwRank = $kwIndex + 1;
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-sm border inline-flex items-center gap-1.5
                                        {{ $isApplied ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800' : 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800' }}">
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold
                                            {{ $isApplied ? 'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-300' : 'bg-indigo-200 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-300' }}">{{ $kwRank }}</span>
                                        @if($isApplied)
                                            <svg class="w-3.5 h-3.5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        @endif
                                        {{ $keyword }}
                                        @if($isApplied)
                                            <span class="text-xs text-green-500 font-medium">Applied</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Content Recommendations -->
                    @if(!empty($analysis['content_recommendations']))
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Content Recommendations</h4>
                            <ul class="space-y-2">
                                @foreach($analysis['content_recommendations'] as $rec)
                                    <li class="flex items-start gap-2 text-sm">
                                        <svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $rec }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Apply Recommendations Preview Modal -->
    @if($showApplyPreview)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50 p-4"
            x-data="{
                applying: false,
                itemCount: {{ count($applyPreview) }},
                completedItems: 0,
                reanalyzing: false,
                intervalId: null,
                applyAndAnimate() {
                    if (this.applying) return;
                    this.applying = true;
                    this.completedItems = 0;
                    this.reanalyzing = false;
                    let delay = Math.max(800, 4000 / this.itemCount);
                    let i = 0;
                    this.intervalId = setInterval(() => {
                        i++;
                        this.completedItems = i;
                        if (i >= this.itemCount) {
                            clearInterval(this.intervalId);
                            this.intervalId = null;
                            setTimeout(() => { this.reanalyzing = true; }, 400);
                        }
                    }, delay);
                    $wire.applyRecommendations();
                }
            }"
            x-on:keydown.escape.window="if (!applying) $wire.closeApplyPreview()">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="applying ? 'Applying Optimizations...' : 'Preview AI Optimizations'"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-show="!applying">The following changes will be applied:</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-show="applying" x-cloak>Please wait while we optimize your post...</p>
                </div>

                <div class="p-6 space-y-3 max-h-[60vh] overflow-y-auto">
                    @forelse($applyPreview as $index => $change)
                        <div class="flex items-start gap-3 p-3 rounded-lg border transition-all duration-500"
                            :class="{
                                'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800': !applying || completedItems > {{ $index }},
                                'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800': applying && completedItems === {{ $index }},
                                'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700': applying && completedItems < {{ $index }}
                            }">
                            <div class="mt-0.5 w-5 h-5 flex-shrink-0 flex items-center justify-center">
                                <svg x-show="!applying && completedItems === 0" class="w-4 h-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <svg x-show="applying && completedItems < {{ $index }}" x-cloak class="w-4 h-4 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <svg x-show="applying && completedItems === {{ $index }}" x-cloak class="w-4 h-4 text-indigo-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg x-show="completedItems > {{ $index }}" x-cloak class="w-4 h-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div class="text-sm flex-1">
                                <span class="font-medium transition-colors duration-300"
                                    :class="{
                                        'text-gray-900 dark:text-white': !applying || completedItems >= {{ $index }},
                                        'text-gray-400 dark:text-gray-600': applying && completedItems < {{ $index }}
                                    }">{{ $change['description'] }}</span>
                                @if(!empty($change['old']) && !empty($change['new']) && ($change['type'] ?? '') !== 'internal_links')
                                    <div class="mt-2 space-y-1" x-show="!applying">
                                        <div class="text-red-600 dark:text-red-400 line-through text-xs">{{ \Illuminate\Support\Str::limit($change['old'], 100) }}</div>
                                        <div class="text-green-600 dark:text-green-400 text-xs">{{ \Illuminate\Support\Str::limit($change['new'], 100) }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <svg class="w-8 h-8 text-green-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400">No additional optimizations needed!</p>
                        </div>
                    @endforelse

                    <!-- Re-analyzing step -->
                    <div x-show="reanalyzing" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                        x-cloak
                        class="flex items-center gap-3 p-3 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 mt-2">
                        <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-400">Re-analyzing SEO scores...</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <button type="button" wire:click="closeApplyPreview"
                        x-show="!applying"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm">
                        Cancel
                    </button>
                    @if(!empty($applyPreview))
                        <button type="button" @click="applyAndAnimate()"
                            x-show="!applying"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                            <svg class="w-4 h-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Apply Changes
                        </button>
                        <div x-show="applying" x-cloak class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 text-indigo-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="reanalyzing ? 'Re-analyzing scores...' : 'Applying ' + completedItems + ' of ' + itemCount + '...'"></span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Analysis Checklist Modal -->
    @if($showAnalysisModal)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50 p-4"
            x-data="{
                steps: [
                    { label: 'Checking content quality & keyword usage', detail: 'Keyword density, placement, LSI keywords' },
                    { label: 'Analyzing technical SEO elements', detail: 'Meta tags, headers, URL slug, image alt text' },
                    { label: 'Evaluating readability & language', detail: 'Flesch score, sentence length, passive voice' },
                    { label: 'Assessing user engagement signals', detail: 'Internal/external links, CTAs, media, structure' },
                    { label: 'Reviewing on-page elements', detail: 'Featured image, formatting, headers, excerpt' },
                    { label: 'Fetching Google Trends data', detail: 'Related queries, trend direction, rising topics' },
                    { label: 'Generating AI feedback & recommendations', detail: 'Strengths, improvements, keyword suggestions' },
                ],
                currentStep: -1,
                finished: false,
                success: false,
                resultGrade: '',
                resultScore: 0,
                intervalId: null,

                init() {
                    this.startAnimation();

                    setTimeout(() => {
                        this.$wire.runAnalysis();
                    }, 300);

                    this.$wire.$on('analysis-finished', (data) => {
                        if (this.intervalId) {
                            clearInterval(this.intervalId);
                            this.intervalId = null;
                        }
                        this.success = data[0]?.success ?? data.success ?? false;
                        this.resultGrade = data[0]?.grade ?? data.grade ?? '';
                        this.resultScore = data[0]?.score ?? data.score ?? 0;
                        this.fastComplete();
                    });
                },

                startAnimation() {
                    this.currentStep = 0;
                    const delays = [900, 800, 1000, 800, 700, 1100, 1500];
                    const scheduleNext = (idx) => {
                        if (idx >= this.steps.length) {
                            if (this.success || !@js($isAnalyzing)) {
                                this.finished = true;
                            }
                            return;
                        }
                        const delay = delays[idx] || 900;
                        setTimeout(() => {
                            this.currentStep = idx;
                            scheduleNext(idx + 1);
                        }, delay);
                    };
                    scheduleNext(1);
                },

                fastComplete() {
                    const remaining = this.steps.length - 1 - this.currentStep;
                    if (remaining <= 0) {
                        this.currentStep = this.steps.length - 1;
                        setTimeout(() => { this.finished = true; }, 400);
                        return;
                    }
                    let i = this.currentStep;
                    const fastInterval = setInterval(() => {
                        i++;
                        this.currentStep = i;
                        if (i >= this.steps.length - 1) {
                            clearInterval(fastInterval);
                            setTimeout(() => { this.finished = true; }, 400);
                        }
                    }, 200);
                },

                gradeColor() {
                    if (['A+', 'A'].includes(this.resultGrade)) return 'text-green-600 dark:text-green-400';
                    if (['B+', 'B'].includes(this.resultGrade)) return 'text-blue-600 dark:text-blue-400';
                    if (['C+', 'C'].includes(this.resultGrade)) return 'text-amber-600 dark:text-amber-400';
                    return 'text-red-600 dark:text-red-400';
                },

                gradeBg() {
                    if (['A+', 'A'].includes(this.resultGrade)) return 'bg-green-500';
                    if (['B+', 'B'].includes(this.resultGrade)) return 'bg-blue-500';
                    if (['C+', 'C'].includes(this.resultGrade)) return 'bg-amber-500';
                    return 'bg-red-500';
                }
            }">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden"
                @click.outside="if (finished) $wire.closeAnalysisModal()">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="finished ? (success ? 'Analysis Complete' : 'Analysis Failed') : 'Analyzing SEO...'"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-show="!finished">Running comprehensive SEO checks</p>
                        </div>
                        <button type="button" x-show="finished" wire:click="closeAnalysisModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Checklist Body -->
                <div class="px-6 py-5 space-y-1 max-h-[60vh] overflow-y-auto">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="flex items-center gap-3 py-2.5 border-b border-gray-100 dark:border-gray-700 last:border-0 transition-all duration-300"
                            :class="{
                                'opacity-40': currentStep < index && !finished,
                                'opacity-100': currentStep >= index || finished
                            }">
                            <!-- Status Icon -->
                            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-300"
                                :class="{
                                    'bg-gray-100 dark:bg-gray-700': currentStep < index && !finished,
                                    'bg-indigo-100 dark:bg-indigo-900/30': currentStep === index && !finished,
                                    'bg-green-100 dark:bg-green-900/30': currentStep > index || (finished && success),
                                    'bg-red-100 dark:bg-red-900/30': finished && !success && currentStep >= index
                                }">
                                <svg x-show="currentStep < index && !finished" class="w-2 h-2 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                                <svg x-show="currentStep === index && !finished" x-transition:enter="transition ease-out duration-200" class="w-4 h-4 text-indigo-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg x-show="(currentStep > index) || (finished && success)" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-0 opacity-0" x-transition:enter-end="scale-100 opacity-100" class="w-4 h-4 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <svg x-show="finished && !success && currentStep <= index" class="w-4 h-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </div>

                            <!-- Label -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium transition-colors duration-300"
                                    :class="{
                                        'text-gray-400 dark:text-gray-600': currentStep < index && !finished,
                                        'text-gray-900 dark:text-white': currentStep === index && !finished,
                                        'text-gray-700 dark:text-gray-300': currentStep > index || finished
                                    }"
                                    x-text="step.label"></div>
                                <div class="text-xs mt-0.5 transition-all duration-300 text-gray-400 dark:text-gray-500"
                                    x-show="currentStep === index && !finished"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-text="step.detail"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer / Result -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <!-- Progress bar while running -->
                    <div x-show="!finished" class="space-y-2">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-500 ease-out"
                                :style="'width: ' + Math.min(100, ((currentStep + 1) / steps.length) * 100) + '%'"></div>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 text-center" x-text="'Step ' + (currentStep + 1) + ' of ' + steps.length"></p>
                    </div>

                    <!-- Result when done -->
                    <div x-show="finished && success" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                        class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg"
                                :class="gradeBg()"
                                x-text="resultGrade"></div>
                            <div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="resultScore + '/100'"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">All checks complete</div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeAnalysisModal"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                            View Results
                        </button>
                    </div>

                    <!-- Error state -->
                    <div x-show="finished && !success" x-transition class="text-center">
                        <p class="text-sm text-red-600 dark:text-red-400 mb-3">Analysis encountered an error</p>
                        <button type="button" wire:click="closeAnalysisModal"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
