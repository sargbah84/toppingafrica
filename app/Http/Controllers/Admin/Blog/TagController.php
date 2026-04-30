<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request): View
    {
        $tags = Tag::withCount('posts')
            ->when($request->query('search'), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.blog.tags.index', compact('tags'));
    }

    /**
     * Show the form for creating a new tag.
     */
    public function create(): View
    {
        return view('admin.blog.tags.create');
    }

    /**
     * Store a newly created tag in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:tags,slug'],
        ]);

        Tag::create($validated);

        return redirect()
            ->route('admin.blog.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Show the form for editing the specified tag.
     */
    public function edit(Tag $tag): View
    {
        return view('admin.blog.tags.edit', compact('tag'));
    }

    /**
     * Update the specified tag in storage.
     */
    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name,'.$tag->id],
            'slug' => ['nullable', 'string', 'max:100', 'unique:tags,slug,'.$tag->id],
        ]);

        $tag->update($validated);

        return redirect()
            ->route('admin.blog.tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        return redirect()
            ->route('admin.blog.tags.index')
            ->with('success', 'Tag deleted successfully.');
    }
}
