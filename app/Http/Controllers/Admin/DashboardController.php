<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with key statistics.
     */
    public function index(Request $request): View
    {
        $stats = [
            'total_posts'      => Post::count(),
            'published_posts'  => Post::where('status', 'published')->count(),
            'draft_posts'      => Post::where('status', 'draft')->count(),
            'scheduled_posts'  => Post::where('status', 'scheduled')->count(),
            'total_views'      => PostView::count(),
            'total_comments'   => Comment::count(),
            'pending_comments' => Comment::where('status', 'pending')->count(),
            'total_categories' => Category::count(),
        ];

        $recentPosts = Post::with('author', 'categories')
            ->latest()
            ->take(5)
            ->get();

        $recentComments = Comment::with('post', 'user')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentPosts', 'recentComments'));
    }
}
