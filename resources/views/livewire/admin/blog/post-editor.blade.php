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

            {{-- Content (TinyMCE Editor) --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden" wire:ignore>
                <textarea id="editor-content">{!! $content !!}</textarea>
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
