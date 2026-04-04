<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;

final class PostViewTracker
{
    public function track(Post $post, Request $request): void
    {
        PostView::recordView(
            $post,
            $request->ip(),
            auth()->id(),
            $request->userAgent()
        );
    }

    public function getViewsForPost(Post $post): int
    {
        return $post->views_count;
    }

    public function getUniqueViewsForPost(Post $post): int
    {
        return PostView::forPost($post)->distinct('ip_hash')->count('ip_hash');
    }

    public function getViewsInPeriod(Post $post, \DateTimeInterface $from, ?\DateTimeInterface $to = null): int
    {
        return PostView::forPost($post)
            ->inPeriod($from, $to)
            ->count();
    }

    public function getViewsByDay(Post $post, int $days = 30): array
    {
        $from = now()->subDays($days);

        return PostView::forPost($post)
            ->inPeriod($from)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
