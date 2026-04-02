<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $featured = Post::published()
            ->featured()
            ->with('author', 'categories')
            ->latest('published_at')
            ->first();

        $posts = Post::published()
            ->with('author', 'categories')
            ->latest('published_at')
            ->paginate(config('blog.per_page', 12));

        $categories = Category::active()
            ->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        $popularPosts = Post::published()
            ->popular()
            ->with('categories')
            ->take(5)
            ->get();

        return view('blog.index', compact('featured', 'posts', 'categories', 'popularPosts'));
    }

    public function show(string $slug): View
    {
        $post = Post::where('slug', $slug)
            ->with('author', 'categories', 'tags')
            ->firstOrFail();

        if (!$post->isPublished()) {
            abort(404);
        }

        $post->incrementViewsCount();

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $post->categories->pluck('id')))
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(config('blog.related_posts_count', 3))
            ->get();

        $categories = Category::active()->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return view('blog.show', compact('post', 'relatedPosts', 'categories'));
    }

    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();

        $posts = Post::published()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with('author', 'categories')
            ->latest('published_at')
            ->paginate(config('blog.per_page', 12));

        $categories = Category::active()->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return view('blog.category', compact('category', 'posts', 'categories'));
    }

    public function tag(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $posts = Post::published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with('author', 'categories')
            ->latest('published_at')
            ->paginate(config('blog.per_page', 12));

        $categories = Category::active()->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return view('blog.tag', compact('tag', 'posts', 'categories'));
    }

    public function search(Request $request): View
    {
        $query = $request->input('q', '');

        $posts = $query
            ? Post::published()->search($query)->with('author', 'categories')->latest('published_at')->paginate(config('blog.per_page', 12))->withQueryString()
            : collect();

        $categories = Category::active()->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return view('blog.search', compact('query', 'posts', 'categories'));
    }

    public function feed(): Response
    {
        $posts = Post::published()
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(20)
            ->get();

        $content = view('blog.feed', compact('posts'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
