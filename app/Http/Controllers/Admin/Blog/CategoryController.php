<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): View
    {
        $categories = Category::withCount('posts')
            ->with('parent')
            ->ordered()
            ->paginate(25);

        return view('admin.blog.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View
    {
        $parentCategories = Category::topLevel()->ordered()->get();

        return view('admin.blog.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'max:20'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'order'       => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        Category::create($validated);

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): View
    {
        $parentCategories = Category::topLevel()
            ->where('id', '!=', $category->id)
            ->ordered()
            ->get();

        return view('admin.blog.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'max:20'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'order'       => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $category->update($validated);

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->posts()->count() > 0) {
            return redirect()
                ->route('admin.blog.categories.index')
                ->with('error', 'Cannot delete a category that has posts. Reassign the posts first.');
        }

        $category->children()->update(['parent_id' => null]);
        $category->delete();

        return redirect()
            ->route('admin.blog.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
