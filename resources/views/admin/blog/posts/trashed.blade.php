<x-layouts.admin title="Trashed Posts" header="Trashed Posts">
    <div class="mb-4">
        <a href="{{ route('admin.blog.posts.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Posts</a>
    </div>
    <livewire:admin.blog.posts-list :show-trashed="true" />
</x-layouts.admin>
