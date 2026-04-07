<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pages.index') }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $pageId ? 'Edit Page' : 'Create New Page' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <span wire:loading wire:target="save,saveDraft,publish,generateWithAi,aiImproveContent,aiRewriteContent,aiGenerateSeo" class="text-sm text-gray-500 dark:text-gray-400">
                <svg class="animate-spin inline-block h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Processing...
            </span>
            @if ($pageId && $slug)
                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('blog.show', now()->addHour(), ['slug' => $slug]) }}" target="_blank"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Preview
                </a>
            @endif
            @if (!$pageId)
                <button wire:click="$toggle('showAiGenerator')" class="px-4 py-2 border border-purple-300 dark:border-purple-700 rounded-lg text-sm font-medium text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                    </svg>
                    AI Generate
                </button>
            @endif
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- AI Generator Panel (Create only) --}}
    @if ($showAiGenerator && !$pageId)
        <div class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-300 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                    </svg>
                    AI Content Generator
                </h3>
                <button wire:click="$toggle('showAiGenerator')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Describe the page content</label>
                    <input type="text" wire:model="aiPrompt" placeholder="e.g. Privacy policy for a news platform"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div class="w-full sm:w-40">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tone</label>
                    <select wire:model="aiTone" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="professional">Professional</option>
                        <option value="conversational">Conversational</option>
                        <option value="formal">Formal</option>
                        <option value="friendly">Friendly</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="generateWithAi" wire:loading.attr="disabled" wire:target="generateWithAi"
                        class="w-full sm:w-auto px-5 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4" wire:loading.remove wire:target="generateWithAi" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        <svg class="animate-spin h-4 w-4" wire:loading wire:target="generateWithAi" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span wire:loading.remove wire:target="generateWithAi">Generate</span>
                        <span wire:loading wire:target="generateWithAi">Generating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Title --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.500ms="title" required placeholder="Page title"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug</label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <input type="checkbox" wire:model.live="autoGenerateSlug" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 h-3.5 w-3.5">
                                Auto
                            </label>
                        </div>
                        <input type="text" wire:model="slug" placeholder="auto-generated-from-title" {{ $autoGenerateSlug ? 'disabled' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:opacity-50">
                        @error('slug') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Content Editor --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Content <span class="text-red-500">*</span></label>
                    @if ($pageId)
                        <div class="flex items-center gap-2">
                            <button wire:click="aiImproveContent" wire:loading.attr="disabled" wire:target="aiImproveContent"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors disabled:opacity-50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                                <span wire:loading.remove wire:target="aiImproveContent">Improve</span>
                                <span wire:loading wire:target="aiImproveContent">Improving...</span>
                            </button>
                            <button type="button"
                                @click="window.tcModal.confirm('This will rewrite the entire content. Continue?', {variant:'warning', confirmText:'Rewrite'}).then(ok => ok && $wire.aiRewriteContent())"
                                wire:loading.attr="disabled" wire:target="aiRewriteContent"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors disabled:opacity-50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/></svg>
                                <span wire:loading.remove wire:target="aiRewriteContent">Rewrite</span>
                                <span wire:loading wire:target="aiRewriteContent">Rewriting...</span>
                            </button>
                        </div>
                    @endif
                </div>
                <div wire:ignore>
                    <textarea id="page-editor-content" class="hidden">{{ $content }}</textarea>
                </div>
                @error('content') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- SEO --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6" x-data="{ open: {{ ($meta_title || $meta_description) ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between">
                    <button @click="open = !open" class="flex items-center gap-2 text-left">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">SEO Settings</h3>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    @if ($pageId)
                        <button wire:click="aiGenerateSeo" wire:loading.attr="disabled" wire:target="aiGenerateSeo"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors disabled:opacity-50"
                            @click="open = true">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                            <span wire:loading.remove wire:target="aiGenerateSeo">Generate SEO</span>
                            <span wire:loading wire:target="aiGenerateSeo">Generating...</span>
                        </button>
                    @endif
                </div>
                <div x-show="open" x-collapse class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Title</label>
                        <input type="text" wire:model="meta_title" placeholder="Defaults to page title"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                        <textarea wire:model="meta_description" rows="2" maxlength="300" placeholder="Brief description for search engines"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                        <p class="mt-1 text-xs text-gray-400">{{ strlen($meta_description) }}/300</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-6">
            {{-- Publish Panel --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Publish</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Status:</span>
                        <span class="font-medium {{ $status === 'published' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">{{ ucfirst($status) }}</span>
                    </div>
                    <div class="flex flex-col gap-2 pt-2">
                        <button wire:click="publish" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                            {{ $pageId ? 'Update & Publish' : 'Publish' }}
                        </button>
                        <button wire:click="saveDraft" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Save Draft
                        </button>
                    </div>
                </div>
            </div>

            {{-- Page Settings --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Page Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Template</label>
                        <select wire:model.live="template"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            @foreach (\App\Models\Page::TEMPLATES as $tplKey => $tpl)
                                <option value="{{ $tplKey }}">{{ $tpl['label'] }}</option>
                            @endforeach
                        </select>
                        @php $currentTpl = \App\Models\Page::TEMPLATES[$template] ?? null; @endphp
                        @if ($currentTpl && ! empty($currentTpl['protected']))
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="inline w-3 h-3 mr-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                Built-in section. The slug, title and intro copy can be edited but the page itself cannot be deleted.
                            </p>
                        @endif
                    </div>

                    @if ($template === 'trending' || $template === 'creators')
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                            <p class="text-xs text-purple-700 dark:text-purple-400">
                                {{ \App\Models\Page::TEMPLATES[$template]['description'] }}
                            </p>
                        </div>
                    @endif

                    @if ($template === 'blog')
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                            <p class="text-xs text-indigo-700 dark:text-indigo-400 mb-3">This page will display blog posts from the selected category or tag in a card grid layout.</p>
                            <div class="space-y-3">
                                {{-- Searchable Category Picker --}}
                                @php
                                    $allCategories = \App\Models\Category::active()->ordered()->get();
                                    $categoryItems = collect([['id' => null, 'name' => 'All Categories']])
                                        ->concat($allCategories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]))
                                        ->values();
                                @endphp
                                <div x-data="{
                                    open: false,
                                    search: '',
                                    items: {{ Js::from($categoryItems) }},
                                    get filtered() { return this.search ? this.items.filter(i => i.name.toLowerCase().includes(this.search.toLowerCase())) : this.items },
                                    selectedName: {{ Js::from($linked_category_id ? $allCategories->firstWhere('id', $linked_category_id)?->name : '') }},
                                    select(item) { $wire.set('linked_category_id', item.id); $wire.set('linked_tag_id', null); this.selectedName = item.name; this.open = false; this.search = ''; },
                                    clear() { $wire.set('linked_category_id', null); this.selectedName = ''; this.search = ''; }
                                }" class="relative">
                                    <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-400 mb-1">Category</label>
                                    <div class="relative">
                                        <input type="text"
                                            x-model="search"
                                            @focus="open = true"
                                            @click="open = true"
                                            :placeholder="selectedName || 'Search categories...'"
                                            class="w-full px-3 py-1.5 border border-indigo-200 dark:border-indigo-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <button x-show="selectedName" @click="clear()" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div x-show="open" @click.away="open = false" x-cloak
                                        class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg">
                                        <template x-for="item in filtered" :key="item.id">
                                            <button type="button" @click="select(item)"
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                                                x-text="item.name"></button>
                                        </template>
                                        <div x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400">No categories match.</div>
                                    </div>
                                </div>

                                <div class="text-center text-xs text-gray-400">— or —</div>

                                {{-- Searchable Tag Picker --}}
                                @php $allTags = \App\Models\Tag::orderBy('name')->get(); @endphp
                                <div x-data="{
                                    open: false,
                                    search: '',
                                    items: {{ Js::from($allTags->map(fn($t) => ['id' => $t->id, 'name' => $t->name])) }},
                                    get filtered() { return this.search ? this.items.filter(i => i.name.toLowerCase().includes(this.search.toLowerCase())) : this.items.slice(0, 50) },
                                    selectedName: {{ Js::from($linked_tag_id ? $allTags->firstWhere('id', $linked_tag_id)?->name : '') }},
                                    select(item) { $wire.set('linked_tag_id', item.id); $wire.set('linked_category_id', null); this.selectedName = item.name; this.open = false; this.search = ''; },
                                    clear() { $wire.set('linked_tag_id', null); this.selectedName = ''; this.search = ''; }
                                }" class="relative">
                                    <label class="block text-xs font-medium text-indigo-700 dark:text-indigo-400 mb-1">Tag</label>
                                    <div class="relative">
                                        <input type="text"
                                            x-model="search"
                                            @focus="open = true"
                                            @click="open = true"
                                            :placeholder="selectedName || 'Search tags...'"
                                            class="w-full px-3 py-1.5 border border-indigo-200 dark:border-indigo-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <button x-show="selectedName" @click="clear()" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div x-show="open" @click.away="open = false" x-cloak
                                        class="absolute z-20 mt-1 w-full max-h-48 overflow-y-auto bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg">
                                        <template x-for="item in filtered" :key="item.id">
                                            <button type="button" @click="select(item)"
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                                                x-text="item.name"></button>
                                        </template>
                                        <div x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400">No tags match. Type to search.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Order</label>
                        <input type="number" wire:model="order" min="0"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        <p class="mt-1 text-xs text-gray-400">Lower numbers appear first in navigation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TinyMCE Editor --}}
    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    @endassets

    @script
    <script>
        $wire.on('content-updated', () => {
            setTimeout(function() {
                const editor = tinymce.get('page-editor-content');
                if (editor) {
                    editor.setContent($wire.get('content') || '');
                }
            }, 100);
        });

        window.addEventListener('insert-content-image', (e) => {
            const url = e.detail?.url;
            const editor = tinymce.get('page-editor-content');
            if (editor && url) {
                editor.insertContent(`<img src="${url}" alt="" />`);
            }
        });

        function initPageEditor() {
            if (typeof tinymce !== 'undefined' && tinymce.get('page-editor-content')) {
                tinymce.get('page-editor-content').remove();
            }

            if (typeof tinymce === 'undefined') {
                setTimeout(initPageEditor, 100);
                return;
            }

            const textarea = document.getElementById('page-editor-content');
            if (!textarea) {
                setTimeout(initPageEditor, 100);
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');

            tinymce.init({
                selector: '#page-editor-content',
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

                    editor.ui.registry.addButton('medialibrary', {
                        icon: 'gallery',
                        tooltip: 'Insert from Media Library',
                        onAction: function() {
                            Livewire.dispatch('open-media-picker', { context: 'content_image', keyword: $wire.get('title') || '' });
                        }
                    });
                }
            });
        }

        initPageEditor();
    </script>
    @endscript

    {{-- Media Picker Modal --}}
    <livewire:admin.media-picker />
</div>
