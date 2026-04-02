<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function index(): Response
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->get();

        $categories = Category::where('is_active', true)
            ->ordered()
            ->get();

        $tags = Tag::whereHas('posts', function ($query): void {
            $query->where('status', 'published')
                ->where('published_at', '<=', now());
        })->get();

        $content = view('sitemap.index', [
            'posts' => $posts,
            'categories' => $categories,
            'tags' => $tags,
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
