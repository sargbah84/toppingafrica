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
            <button
                type="button"
                x-on:click="$dispatch('open-ai-generator')"
                class="px-4 py-2 border border-indigo-300 dark:border-indigo-700 rounded-lg text-sm font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                </svg>
                AI Generate
            </button>
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

            {{-- Content (TipTap WYSIWYG) --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
                 x-data="tiptapEditor($wire.entangle('content'), 'content')"
                 wire:ignore>
                {{-- Toolbar --}}
                <div class="tiptap-toolbar">
                    <button type="button" @click="toggleBold()" :class="{ 'is-active': isActive('bold') }" title="Bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6zM6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
                    </button>
                    <button type="button" @click="toggleItalic()" :class="{ 'is-active': isActive('italic') }" title="Italic">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10 4h4m-2 0l-4 16m0 0h4m2-16l-4 16"/></svg>
                    </button>
                    <button type="button" @click="toggleUnderline()" :class="{ 'is-active': isActive('underline') }" title="Underline">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4v7a5 5 0 0 0 10 0V4M5 21h14"/></svg>
                    </button>
                    <button type="button" @click="toggleStrike()" :class="{ 'is-active': isActive('strike') }" title="Strikethrough">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 4H9a3 3 0 0 0 0 6h2m4 0H9a3 3 0 0 0 0 6h7M4 12h16"/></svg>
                    </button>

                    <div class="separator"></div>

                    <button type="button" @click="toggleHeading(2)" :class="{ 'is-active': isActive('heading', { level: 2 }) }" title="Heading 2">
                        <span class="text-xs font-bold">H2</span>
                    </button>
                    <button type="button" @click="toggleHeading(3)" :class="{ 'is-active': isActive('heading', { level: 3 }) }" title="Heading 3">
                        <span class="text-xs font-bold">H3</span>
                    </button>
                    <button type="button" @click="toggleHeading(4)" :class="{ 'is-active': isActive('heading', { level: 4 }) }" title="Heading 4">
                        <span class="text-xs font-bold">H4</span>
                    </button>

                    <div class="separator"></div>

                    <button type="button" @click="toggleBulletList()" :class="{ 'is-active': isActive('bulletList') }" title="Bullet List">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/></svg>
                    </button>
                    <button type="button" @click="toggleOrderedList()" :class="{ 'is-active': isActive('orderedList') }" title="Ordered List">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.242 5.992h12m-12 6.003h12m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H4.99m-3.873 3.75h.003m-.003 0h3.75m-3.753 0c0-.64.217-1.224.563-1.688m-3.313 8.19v-.003m0 0h3.75m-3.75 0c0 .638.218 1.224.563 1.689"/></svg>
                    </button>
                    <button type="button" @click="toggleBlockquote()" :class="{ 'is-active': isActive('blockquote') }" title="Blockquote">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                    </button>
                    <button type="button" @click="toggleCodeBlock()" :class="{ 'is-active': isActive('codeBlock') }" title="Code Block">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/></svg>
                    </button>

                    <div class="separator"></div>

                    <button type="button" @click="setTextAlign('left')" :class="{ 'is-active': isActive({ textAlign: 'left' }) }" title="Align Left">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h10.5m-10.5 5.25h16.5"/></svg>
                    </button>
                    <button type="button" @click="setTextAlign('center')" :class="{ 'is-active': isActive({ textAlign: 'center' }) }" title="Align Center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6.75 12h10.5M3.75 17.25h16.5"/></svg>
                    </button>

                    <div class="separator"></div>

                    <button type="button" @click="addLink()" :class="{ 'is-active': isActive('link') }" title="Add Link">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m9.86-1.03a4.5 4.5 0 0 0-1.242-7.244l-4.5-4.5a4.5 4.5 0 0 0-6.364 6.364l1.757 1.757"/></svg>
                    </button>
                    <button type="button" @click="addImage()" title="Add Image">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V5.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v14.25a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                    </button>
                    <button type="button" @click="addYoutube()" title="Add YouTube Video">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"/></svg>
                    </button>
                    <button type="button" @click="setHorizontalRule()" title="Horizontal Rule">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5"/></svg>
                    </button>

                    <div class="separator"></div>

                    <button type="button" @click="undo()" title="Undo">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    </button>
                    <button type="button" @click="redo()" title="Redo">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3"/></svg>
                    </button>
                </div>

                {{-- Editor Area --}}
                <div x-ref="editor" class="border-0 bg-white dark:bg-gray-800 min-h-[400px]"></div>
            </div>
            @error('content') <p class="mt-1 text-sm text-red-600 dark:text-red-400 px-1">{{ $message }}</p> @enderror

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

    {{-- Media Picker Modal --}}
    <livewire:admin.media-picker />

    {{-- AI Post Generator Modal --}}
    <livewire:admin.blog.post-generator />
</div>
