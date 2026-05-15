<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Polymorphic view-tracking service. Replaces the old App\Services\Blog\PostViewTracker
 * (which is kept as a thin BC alias). Handles tracking and aggregation queries
 * for any model that has a view-tracking integration — currently Post and Creator.
 */
class ViewTracker
{
    public function track(Model $subject, Request $request): void
    {
        View::recordView(
            $subject,
            $request->ip(),
            auth()->id(),
            $request->userAgent(),
            $request->headers->get('referer')
        );
    }

    public function getViewsFor(Model $subject): int
    {
        return View::query()->forViewable($subject)->count();
    }

    public function getUniqueViewsFor(Model $subject): int
    {
        return View::query()->forViewable($subject)->distinct('ip_hash')->count('ip_hash');
    }

    public function getViewsInPeriod(Model $subject, \DateTimeInterface $from, ?\DateTimeInterface $to = null): int
    {
        return View::query()
            ->forViewable($subject)
            ->inPeriod($from, $to)
            ->count();
    }

    public function getViewsByDay(Model $subject, int $days = 30): array
    {
        $from = now()->subDays($days);

        return View::query()
            ->forViewable($subject)
            ->inPeriod($from)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
