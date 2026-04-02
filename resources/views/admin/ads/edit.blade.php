<x-layouts.admin title="Edit Ad" header="Edit Ad">
    <div class="max-w-2xl">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <form method="POST" action="{{ route('admin.ads.update', $ad) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $ad->name) }}" required
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Position --}}
                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Position</label>
                    <select name="position" id="position" required
                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <option value="header" {{ old('position', $ad->position) === 'header' ? 'selected' : '' }}>Header</option>
                        <option value="sidebar" {{ old('position', $ad->position) === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                        <option value="in_article" {{ old('position', $ad->position) === 'in_article' ? 'selected' : '' }}>In Article</option>
                        <option value="after_content" {{ old('position', $ad->position) === 'after_content' ? 'selected' : '' }}>After Content</option>
                        <option value="footer" {{ old('position', $ad->position) === 'footer' ? 'selected' : '' }}>Footer</option>
                    </select>
                    @error('position')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Code --}}
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ad Code (HTML/AdSense)</label>
                    <textarea name="code" id="code" rows="6"
                              class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 font-mono"
                              placeholder="Paste your AdSense code or custom HTML here...">{{ old('code', $ad->code) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty if using image + URL instead.</p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Image URL --}}
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Image URL</label>
                    <input type="text" name="image" id="image" value="{{ old('image', $ad->image) }}"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                           placeholder="/storage/ads/banner.jpg or https://...">
                    @if($ad->image)
                        <div class="mt-2">
                            <img src="{{ $ad->image }}" alt="{{ $ad->name }}" class="max-h-24 rounded border border-gray-200 dark:border-gray-700">
                        </div>
                    @endif
                    @error('image')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Click URL --}}
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Click URL</label>
                    <input type="url" name="url" id="url" value="{{ old('url', $ad->url) }}" placeholder="https://..."
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('url')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Active --}}
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $ad->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-600 dark:bg-gray-700">
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                </div>

                {{-- Order --}}
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display Order</label>
                    <input type="number" name="order" id="order" value="{{ old('order', $ad->order) }}" min="0"
                           class="mt-1 block w-32 rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('order')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Schedule --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="starts_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input type="datetime-local" name="starts_at" id="starts_at"
                               value="{{ old('starts_at', $ad->starts_at?->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to start immediately.</p>
                        @error('starts_at')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="ends_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input type="datetime-local" name="ends_at" id="ends_at"
                               value="{{ old('ends_at', $ad->ends_at?->format('Y-m-d\TH:i')) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to run indefinitely.</p>
                        @error('ends_at')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="{{ route('admin.ads.index') }}"
                       class="rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Update Ad
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
