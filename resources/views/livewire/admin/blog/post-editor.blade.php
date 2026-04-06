<div x-data="{ seoOpen: @entangle('showSeoPanel'), socialOpen: @entangle('showSocialPanel') }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.blog.posts.index') }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $postId ? 'Edit Post' : 'Create New Post' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <span wire:loading wire:target="save,saveDraft,publish,schedule" class="text-sm text-gray-500 dark:text-gray-400">
                <svg class="animate-spin inline-block h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Saving...
            </span>
            @if($postId && $slug)
                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('blog.show', now()->addHour(), ['slug' => $slug]) }}" target="_blank"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Preview
                </a>
            @endif
            <button wire:click="saveDraft" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Save Draft
            </button>
            <button wire:click="publish" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                {{ $postId ? 'Update & Publish' : 'Publish' }}
            </button>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span class="text-sm font-medium text-red-800 dark:text-red-300">Please fix the following errors:</span>
            </div>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Title --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                        <input
                            wire:model.live.debounce.500ms="title"
                            type="text"
                            id="title"
                            placeholder="Enter post title..."
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg"
                        />
                        @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                        <div class="flex items-center gap-2">
                            <input
                                wire:model="slug"
                                type="text"
                                id="slug"
                                placeholder="post-url-slug"
                                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                            />
                            <button wire:click="generateSlug" class="px-3 py-2 text-sm text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                                Regenerate
                            </button>
                        </div>
                        <label class="flex items-center gap-2 mt-2">
                            <input type="checkbox" wire:model="autoGenerateSlug" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">Auto-generate from title</span>
                        </label>
                        @error('slug') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            @if(in_array($post_type, ['quiz', 'trivia', 'poll']))
                {{-- Type-Specific Content Editor --}}

                {{-- Description (optional intro text) --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <label for="content-intro" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Introduction (optional)</label>
                    <textarea
                        wire:model="content"
                        id="content-intro"
                        rows="3"
                        placeholder="Brief intro displayed above the {{ $post_type }}..."
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                    ></textarea>
                </div>

                @if($post_type === 'quiz')
                    {{-- Quiz Editor --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Quiz Questions</h3>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Passing Score (%)</label>
                                <input type="number" wire:model="type_data.passing_score" min="0" max="100"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model="type_data.show_answers"
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    Show correct answers after
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach($type_data['questions'] ?? [] as $qi => $question)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Question {{ $qi + 1 }}</span>
                                        <button type="button" wire:click="removeQuizQuestion({{ $qi }})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                                    </div>

                                    <input type="text" wire:model="type_data.questions.{{ $qi }}.question" placeholder="Enter question..."
                                           class="w-full px-3 py-2 mb-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">

                                    <div class="space-y-2 mb-3">
                                        @foreach($question['answers'] ?? [] as $ai => $answer)
                                            <div class="flex items-center gap-2">
                                                <button type="button" wire:click="setCorrectAnswer({{ $qi }}, {{ $ai }})"
                                                        class="flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center {{ ($answer['is_correct'] ?? false) ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-green-400' }}">
                                                    @if($answer['is_correct'] ?? false)
                                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                                    @endif
                                                </button>
                                                <input type="text" wire:model="type_data.questions.{{ $qi }}.answers.{{ $ai }}.text" placeholder="Answer option..."
                                                       class="flex-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                                @if(count($question['answers']) > 2)
                                                    <button type="button" wire:click="removeQuizAnswer({{ $qi }}, {{ $ai }})" class="text-gray-400 hover:text-red-500">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" wire:click="addQuizAnswer({{ $qi }})" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-medium">+ Add Answer</button>

                                    <div class="mt-3">
                                        <input type="text" wire:model="type_data.questions.{{ $qi }}.explanation" placeholder="Explanation (shown after answering)..."
                                               class="w-full px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addQuizQuestion"
                                class="mt-4 w-full py-2.5 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-500 dark:text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-colors">
                            + Add Question
                        </button>
                        @error('type_data.questions') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @elseif($post_type === 'trivia')
                    {{-- Trivia Editor --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Trivia Facts</h3>

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Source URL (optional)</label>
                            <input type="url" wire:model="type_data.source_url" placeholder="https://..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="space-y-3">
                            @foreach($type_data['facts'] ?? [] as $fi => $fact)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fact {{ $fi + 1 }}</span>
                                        <button type="button" wire:click="removeTriviaFact({{ $fi }})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                                    </div>
                                    <textarea wire:model="type_data.facts.{{ $fi }}.text" rows="2" placeholder="Enter a fun fact..."
                                              class="w-full px-3 py-2 mb-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                                    <input type="text" wire:model="type_data.facts.{{ $fi }}.source" placeholder="Source (optional)"
                                           class="w-full px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500">
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addTriviaFact"
                                class="mt-4 w-full py-2.5 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-500 dark:text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-colors">
                            + Add Fact
                        </button>
                        @error('type_data.facts') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @elseif($post_type === 'poll')
                    {{-- Poll Editor --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Poll Options</h3>

                        <div class="space-y-3 mb-4">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="type_data.allow_multiple"
                                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                Allow multiple selections
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="type_data.show_results_before_vote"
                                       class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                Show results before voting
                            </label>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">End Date (optional)</label>
                                <input type="datetime-local" wire:model="type_data.ends_at"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="space-y-2">
                            @foreach($type_data['options'] ?? [] as $oi => $option)
                                <div class="flex items-center gap-2">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold">{{ $oi + 1 }}</span>
                                    <input type="text" wire:model="type_data.options.{{ $oi }}.text" placeholder="Option {{ $oi + 1 }}..."
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                    @if(count($type_data['options']) > 2)
                                        <button type="button" wire:click="removePollOption({{ $oi }})" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addPollOption"
                                class="mt-4 w-full py-2.5 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-500 dark:text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-colors">
                            + Add Option
                        </button>
                        @error('type_data.options') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                @endif
            @else
                {{-- Content (TinyMCE Editor) --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden" wire:ignore>
                    <textarea id="editor-content">{!! $content !!}</textarea>
                </div>
                @error('content') <p class="mt-1 text-sm text-red-600 dark:text-red-400 px-1">{{ $message }}</p> @enderror
            @endif

            {{-- Excerpt --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <label for="excerpt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Excerpt</label>
                <textarea
                    wire:model="excerpt"
                    id="excerpt"
                    rows="3"
                    placeholder="Brief summary of the post..."
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                ></textarea>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ Str::length($excerpt) }}/500 characters</p>
                @error('excerpt') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- SEO Accordion --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <button
                    @click="seoOpen = !seoOpen"
                    class="w-full flex items-center justify-between px-6 py-4 text-left"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">SEO Settings</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="seoOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="seoOpen" x-collapse class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 space-y-4">
                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Title</label>
                        <input
                            wire:model="meta_title"
                            type="text"
                            id="meta_title"
                            placeholder="SEO title (max 70 characters)"
                            maxlength="70"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ Str::length($meta_title) }}/70 characters</p>
                        @error('meta_title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                        <textarea
                            wire:model="meta_description"
                            id="meta_description"
                            rows="2"
                            placeholder="SEO description (max 160 characters)"
                            maxlength="160"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ Str::length($meta_description) }}/160 characters</p>
                        @error('meta_description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="focus_keyword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Focus Keyword</label>
                        <input
                            wire:model="focus_keyword"
                            type="text"
                            id="focus_keyword"
                            placeholder="Primary keyword for this post"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        />
                        @error('focus_keyword') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Social Sharing Accordion --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <button
                    @click="socialOpen = !socialOpen"
                    class="w-full flex items-center justify-between px-6 py-4 text-left"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Social Sharing / Open Graph</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="socialOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="socialOpen" x-collapse class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 space-y-4">
                    <div>
                        <label for="og_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OG Title</label>
                        <input
                            wire:model="og_meta.og_title"
                            type="text"
                            id="og_title"
                            placeholder="Open Graph title"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        />
                    </div>
                    <div>
                        <label for="og_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OG Description</label>
                        <textarea
                            wire:model="og_meta.og_description"
                            id="og_description"
                            rows="2"
                            placeholder="Open Graph description"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        ></textarea>
                    </div>
                    <div>
                        <label for="og_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OG Image URL</label>
                        <input
                            wire:model="og_meta.og_image"
                            type="url"
                            id="og_image"
                            placeholder="https://example.com/image.jpg"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        />
                    </div>
                    <div>
                        <label for="twitter_card" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Twitter Card Type</label>
                        <select
                            wire:model="og_meta.twitter_card"
                            id="twitter_card"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        >
                            <option value="summary">Summary</option>
                            <option value="summary_large_image">Summary with Large Image</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-6">
            {{-- Post Type --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Post Type</h3>
                <select
                    wire:model.live="post_type"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                >
                    @foreach ($postTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
                @error('post_type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Status / Publish Controls --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Publish</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Status:</span>
                        @php
                            $statusColors = [
                                'published' => 'text-green-600 dark:text-green-400',
                                'draft' => 'text-yellow-600 dark:text-yellow-400',
                                'scheduled' => 'text-blue-600 dark:text-blue-400',
                            ];
                        @endphp
                        <span class="font-medium {{ $statusColors[$status] ?? 'text-gray-600 dark:text-gray-400' }}">{{ ucfirst($status) }}</span>
                    </div>
                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="is_featured" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Featured post</span>
                        </label>
                    </div>
                    <div>
                        <label for="scheduled_at" class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Schedule for</label>
                        <input
                            wire:model="scheduled_at"
                            type="datetime-local"
                            id="scheduled_at"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                        />
                        @error('scheduled_at') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-2 pt-2">
                        <button wire:click="schedule" class="w-full px-4 py-2 border border-blue-300 dark:border-blue-700 rounded-lg text-sm font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                            Schedule
                        </button>
                    </div>
                </div>
            </div>

            {{-- Categories --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Categories</h3>
                <div class="max-h-48 overflow-y-auto space-y-2 pr-1">
                    @foreach ($this->availableCategories as $category)
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                wire:model="selectedCategories"
                                value="{{ $category->id }}"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                        </label>
                        @if ($category->children && $category->children->count())
                            @foreach ($category->children as $child)
                                <label class="flex items-center gap-2 ml-5">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedCategories"
                                        value="{{ $child->id }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                                    />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $child->name }}</span>
                                </label>
                            @endforeach
                        @endif
                    @endforeach
                </div>
                @error('selectedCategories') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Tags --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Tags</h3>
                <div class="flex items-center gap-2 mb-3">
                    <input
                        wire:model="tagInput"
                        wire:keydown.enter.prevent="addTag"
                        type="text"
                        placeholder="Add a tag..."
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                    />
                    <button wire:click="addTag" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition-colors">
                        Add
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($selectedTags as $index => $tag)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                            {{ $tag }}
                            <button wire:click="removeTag({{ $index }})" class="hover:text-indigo-900 dark:hover:text-indigo-200">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- Featured Image --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Featured Image</h3>

                @if ($existingFeaturedImageUrl && !$featuredImage)
                    <div class="relative mb-3">
                        <img src="{{ $existingFeaturedImageUrl }}" alt="Featured image" class="w-full h-40 object-cover rounded-lg" />
                        <button
                            wire:click="removeFeaturedImage"
                            class="absolute top-2 right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                        >
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if ($featuredImage)
                    <div class="relative mb-3">
                        <img src="{{ $featuredImage->temporaryUrl() }}" alt="Preview" class="w-full h-40 object-cover rounded-lg" />
                        <button
                            wire:click="removeFeaturedImage"
                            class="absolute top-2 right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                        >
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                <button
                    type="button"
                    x-on:click="$dispatch('open-media-picker', { context: 'featured_image', keyword: $wire.focus_keyword || $wire.title || '' })"
                    class="w-full px-4 py-2 border border-indigo-300 dark:border-indigo-700 rounded-lg text-sm font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                    Choose from Media Library
                </button>

                @error('featuredImage') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    {{-- SEO Analysis Panel (in right sidebar area) --}}
    <div class="mt-6">
        <livewire:admin.blog.seo-analysis-panel :post-id="$postId" />
    </div>

    {{-- TinyMCE Editor --}}
    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    @endassets

    @script
    <script>
        // Update editor when AI content is loaded
        $wire.on('content-updated', () => {
            setTimeout(function() {
                const editor = tinymce.get('editor-content');
                if (editor) {
                    editor.setContent($wire.get('content') || '');
                }
            }, 100);
        });

        // Insert image from media picker
        window.addEventListener('insert-content-image', (e) => {
            const url = e.detail?.url;
            const editor = tinymce.get('editor-content');
            if (editor && url) {
                editor.insertContent(`<img src="${url}" alt="" />`);
            }
        });

        function initEditor() {
            if (typeof tinymce !== 'undefined' && tinymce.get('editor-content')) {
                tinymce.get('editor-content').remove();
            }

            if (typeof tinymce === 'undefined') {
                setTimeout(initEditor, 100);
                return;
            }

            const textarea = document.getElementById('editor-content');
            if (!textarea) {
                setTimeout(initEditor, 100);
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');

            tinymce.init({
                selector: '#editor-content',
                height: 500,
                menubar: true,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic underline backcolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'link image medialibrary media | removeformat | code fullscreen | help',
                content_style: `
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                        font-size: 16px;
                        line-height: 1.7;
                        padding: 1rem;
                        ${isDark ? 'background: #1f2937; color: #e5e7eb;' : ''}
                    }
                    img { max-width: 100%; height: auto; border-radius: 0.25rem; }
                `,
                skin: isDark ? 'oxide-dark' : 'oxide',
                content_css: isDark ? 'dark' : 'default',
                promotion: false,
                branding: false,
                license_key: 'gpl',
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                media_live_embeds: true,
                setup: function(editor) {
                    editor.on('init', function() {
                        const initialContent = $wire.get('content') || '';
                        if (initialContent) {
                            editor.setContent(initialContent);
                        }
                    });

                    editor.on('change keyup blur', function() {
                        editor.save();
                        $wire.set('content', editor.getContent());
                    });

                    // Custom button for media library
                    editor.ui.registry.addButton('medialibrary', {
                        icon: 'gallery',
                        tooltip: 'Insert from Media Library',
                        onAction: function() {
                            const keyword = $wire.get('focus_keyword') || $wire.get('title') || '';
                            Livewire.dispatch('open-media-picker', { context: 'content_image', keyword: keyword });
                        }
                    });
                }
            });
        }

        initEditor();
    </script>
    @endscript

    {{-- Media Picker Modal --}}
    <livewire:admin.media-picker />

    {{-- AI Post Generator Modal --}}
    <livewire:admin.blog.post-generator />
</div>
