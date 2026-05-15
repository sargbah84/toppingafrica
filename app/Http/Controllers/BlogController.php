<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Creator;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\PostViewTracker;
use App\Services\ContentShortcodeParser;
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

        // Most Popular + Trending: ranked by views from the past 7 days.
        // Pull 5 in one query, then split: top 2 → Most Popular, next 3 → Trending.
        $weeklyPopular = Post::published()
            ->with('author', 'categories')
            ->withCount(['views as weekly_views_count' => fn ($q) => $q->where('viewed_at', '>=', now()->subWeek())])
            ->orderByDesc('weekly_views_count')
            ->latest('published_at')
            ->take(5)
            ->get();

        $mostPopular = $weeklyPopular->take(2);
        $trending = $weeklyPopular->slice(2, 3)->values();

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

        // Creators carousel — only show creators with a profile image
        $creators = Creator::published()
            ->where(function ($query) {
                $query->whereHas('media', fn ($q) => $q->where('collection_name', 'profile_image'))
                    ->orWhere('profile_image_url', '!=', '');
            })
            ->inRandomOrder()
            ->take(24)
            ->get();

        // Top creators widget — by page views this week, only with profile images
        $topCreators = Creator::published()
            ->where(function ($query) {
                $query->whereHas('media', fn ($q) => $q->where('collection_name', 'profile_image'))
                    ->orWhere('profile_image_url', '!=', '');
            })
            ->withCount(['views' => fn ($q) => $q->where('created_at', '>=', now()->startOfWeek())])
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        return view('blog.index', compact(
            'heroPost', 'mostPopular', 'trending', 'featuredVideos',
            'tvPosts', 'latestStories', 'editorsPicked', 'categories', 'creators', 'topCreators'
        ));
    }

    public function show(Request $request, string $slug): View
    {
        // Check for a published page first (or draft with preview token)
        $page = Page::where('slug', $slug)->first();
        if ($page) {
            if (! $page->isPublished() && ! $request->hasValidSignature()) {
                abort(404);
            }

            if ($page->isBlogTemplate()) {
                return $this->renderBlogPage($page, $request);
            }

            if ($page->isTrendingTemplate()) {
                return $this->renderTrendingPage($page, $request);
            }

            if ($page->isCreatorsTemplate()) {
                return $this->renderCreatorsPage($page, $request);
            }

            return view('blog.page', compact('page'));
        }

        $post = Post::where('slug', $slug)
            ->with('author', 'categories', 'tags')
            ->firstOrFail();

        if (! $post->isPublished() && ! $request->hasValidSignature()) {
            abort(404);
        }

        app(PostViewTracker::class)->track($post, $request);

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

        // Parse shortcodes (e.g. [poll:slug]) in article content
        if ($post->post_type === 'article') {
            $post->content = app(ContentShortcodeParser::class)->parse($post->content);
        }

        // Sidebar data
        $popularPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->with('categories')
            ->popular()
            ->take(5)
            ->get();

        $topCreators = Creator::published()
            ->withCount(['views' => fn ($q) => $q->where('created_at', '>=', now()->startOfWeek())])
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        return view('blog.show', compact('post', 'relatedPosts', 'categories', 'popularPosts', 'topCreators'));
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

    public function suggest(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['creators' => [], 'posts' => []]);
        }

        $creators = Creator::query()
            ->where(fn ($query) => $query->where('status', 'published')->orWhere('status', 'claimed'))
            ->search($q)
            ->orderByDesc('follower_count')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'category', 'country', 'profile_image_url', 'follower_count'])
            ->map(fn ($c) => [
                'name' => $c->name,
                'slug' => $c->slug,
                'category' => $c->category,
                'country' => $c->country,
                'image' => $c->profile_image_url,
                'initials' => $c->initials,
                'url' => route('creators.show', $c->slug),
            ]);

        $posts = Post::published()
            ->search($q)
            ->with('author')
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'author_id'])
            ->map(fn ($p) => [
                'title' => $p->title,
                'excerpt' => \Illuminate\Support\Str::limit(strip_tags((string) $p->excerpt), 90),
                'author' => $p->author?->name,
                'image' => $p->featured_image_thumbnail,
                'url' => url('/' . $p->slug),
            ]);

        return response()->json(['creators' => $creators, 'posts' => $posts]);
    }

    public function search(Request $request): View
    {
        $query = $request->input('q', '');

        $posts = $query
            ? Post::published()->search($query)->with('author', 'categories')->latest('published_at')->paginate(config('blog.per_page', 12))->withQueryString()
            : collect();

        // Creators are a second, independent result set — cap at 12 since they
        // share the search page with paginated posts. Visibility filter matches
        // the public directory: published + claimed (not pending/rejected).
        $creators = $query
            ? Creator::query()
                ->where(fn ($q) => $q->where('status', 'published')->orWhere('status', 'claimed'))
                ->search($query)
                ->orderByDesc('follower_count')
                ->limit(12)
                ->get()
            : collect();

        $categories = Category::active()->ordered()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();

        return view('blog.search', compact('query', 'posts', 'creators', 'categories'));
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

    private function renderBlogPage(Page $page, Request $request): View
    {
        $query = Post::published()
            ->with('author', 'categories')
            ->latest('published_at');

        if ($page->linked_category_id) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $page->linked_category_id));
        } elseif ($page->linked_tag_id) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $page->linked_tag_id));
        }

        $posts = $query->paginate(config('blog.posts_per_page', 12));

        return view('blog.page-blog', compact('page', 'posts'));
    }

    private function renderTrendingPage(Page $page, Request $request): View
    {
        // The Trending listing itself is rendered by the existing
        // <livewire:trending-page> full-page component. The wrapper view
        // injects the page's intro copy above it.
        return view('blog.page-trending', compact('page'));
    }

    private function renderCreatorsPage(Page $page, Request $request): View
    {
        // Reuse the existing creator directory query (previously in
        // CreatorController::index) so the listing keeps its filter UI.
        $query = Creator::with('socialLinks')
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('status', 'claimed');
            });

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($country = $request->query('country')) {
            $query->where('country', $country);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Always randomize so niches and countries are mixed within the result set.
        // Seed is stable per-day so pagination stays consistent while a user is browsing.
        $seed = (int) ($request->query('seed') ?: now()->format('Ymd'));
        $query->inRandomOrder($seed);

        $creators = $query->paginate(24)->withQueryString();

        $categories = Creator::where(function ($q) {
            $q->where('status', 'published')->orWhere('status', 'claimed');
        })
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $countries = Creator::where(function ($q) {
            $q->where('status', 'published')->orWhere('status', 'claimed');
        })
            ->distinct()
            ->pluck('country')
            ->sort()
            ->values();

        return view('creators.index', compact('page', 'creators', 'categories', 'countries'));
    }
}
