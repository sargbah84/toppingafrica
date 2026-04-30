<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PostController extends Controller
{
    /**
     * Display a listing of posts with optional status filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $posts = Post::with('author', 'categories', 'tags')
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('search'), fn ($query, string $search) => $query->search($search))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = [
            'all' => Post::count(),
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'scheduled' => Post::where('status', 'scheduled')->count(),
            'trashed' => Post::onlyTrashed()->count(),
        ];

        return view('admin.blog.posts.index', compact('posts', 'statusCounts', 'status'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(): View
    {
        $categories = Category::active()->ordered()->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.blog.posts.create', compact('categories', 'tags'));
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'post_type' => ['nullable', 'string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'is_featured' => ['boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $post = Post::create([
            ...Arr::except($validated, ['categories', 'tags']),
            'author_id' => $request->user()->id,
        ]);

        if (! empty($validated['categories'])) {
            $post->categories()->sync($validated['categories']);
        }

        if (! empty($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('success', 'Post created successfully.');
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post): View
    {
        $post->load('categories', 'tags');
        $categories = Category::active()->ordered()->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.blog.posts.edit', compact('post', 'categories', 'tags'));
    }

    /**
     * Update the specified post in storage.
     */
    public function update(Request $request, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug,'.$post->id],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'post_type' => ['nullable', 'string', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'is_featured' => ['boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $post->update(Arr::except($validated, ['categories', 'tags']));

        $post->categories()->sync($validated['categories'] ?? []);
        $post->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('admin.blog.posts.edit', $post)
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Soft-delete the specified post.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->route('admin.blog.posts.index')
            ->with('success', 'Post moved to trash.');
    }

    /**
     * Display a listing of trashed (soft-deleted) posts.
     */
    public function trashed(Request $request): View
    {
        $posts = Post::onlyTrashed()
            ->with('author', 'categories')
            ->latest('deleted_at')
            ->paginate(20);

        return view('admin.blog.posts.trashed', compact('posts'));
    }

    /**
     * Restore a trashed post.
     */
    public function restore(int $id): RedirectResponse
    {
        $post = Post::onlyTrashed()->findOrFail($id);
        $post->restore();

        return redirect()
            ->route('admin.blog.posts.trashed')
            ->with('success', 'Post restored successfully.');
    }

    /**
     * Permanently delete a trashed post.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $post = Post::onlyTrashed()->findOrFail($id);
        $post->categories()->detach();
        $post->tags()->detach();
        $post->forceDelete();

        return redirect()
            ->route('admin.blog.posts.trashed')
            ->with('success', 'Post permanently deleted.');
    }
}
