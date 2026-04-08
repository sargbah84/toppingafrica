<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Services\ViewTracker;
use Illuminate\Http\Request;

/**
 * Backward-compatibility shim for the old App\Services\Blog\PostViewTracker.
 *
 * BlogController already imports this class and calls track($post, $request)
 * once per post page load. Rather than touching every call site, we keep the
 * old class name and forward all method calls to the new generic ViewTracker.
 *
 * New code should inject App\Services\ViewTracker directly. This shim can be
 * deleted once BlogController is updated, but there's no urgency.
 */
final class PostViewTracker
{
    public function __construct(
        private readonly ViewTracker $tracker = new ViewTracker(),
    ) {}

    public function track(Post $post, Request $request): void
    {
        $this->tracker->track($post, $request);
    }

    public function getViewsForPost(Post $post): int
    {
        return $this->tracker->getViewsFor($post);
    }

    public function getUniqueViewsForPost(Post $post): int
    {
        return $this->tracker->getUniqueViewsFor($post);
    }

    public function getViewsInPeriod(Post $post, \DateTimeInterface $from, ?\DateTimeInterface $to = null): int
    {
        return $this->tracker->getViewsInPeriod($post, $from, $to);
    }

    public function getViewsByDay(Post $post, int $days = 30): array
    {
        return $this->tracker->getViewsByDay($post, $days);
    }
}
