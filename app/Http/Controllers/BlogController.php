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
        // Hero: 3 latest featured posts
        $heroPost = Post::published()
            ->featured()
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(3)
            ->get();

        // Most Popular: top 2 by views
        $mostPopular = Post::published()
            ->popular()
            ->with('author', 'categories')
            ->take(2)
            ->get();

        // Trending: next 3 popular posts (skip 2)
        $trending = Post::published()
            ->popular()
            ->with('author', 'categories')
            ->skip(2)
            ->take(3)
            ->get();

        // Featured Videos: from "Music Videos" or "Featured Videos" categories
        $featuredVideos = Post::published()
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', [3, 27]))
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(4)
            ->get();

        // Movies + TV posts for "Explore What's On TV"
        $tvPosts = Post::published()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', 4))
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(5)
            ->get();

        // Latest Stories (skip featured ones)
        $latestStories = Post::published()
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(5)
            ->get();

        // Editor's Picked: featured posts that aren't in hero
        $editorsPicked = Post::published()
            ->featured()
            ->with('categories')
            ->latest('published_at')
            ->skip(3)
            ->take(5)
            ->get();

        $categories = Category::active()
            ->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return view('blog.index', compact(
            'heroPost', 'mostPopular', 'trending', 'featuredVideos',
            'tvPosts', 'latestStories', 'editorsPicked', 'categories'
        ));
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
        $category = Category::where('slug', $slug)->active()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->firstOrFail();

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
