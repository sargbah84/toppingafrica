<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Backward-compatibility alias for the polymorphic View model.
 *
 * Existing code that imports App\Models\PostView and queries it (e.g. via
 * PostView::query(), PostView::where(...), $user->postViews(), etc.) keeps
 * working unchanged — the underlying table, model logic, and behavior all
 * live in App\Models\View now. This subclass just preserves the old class
 * name so we don't have to update every call site at once.
 *
 * The static recordView() helper here forwards to View::recordView() for
 * the (Post, ...) signature that BlogController previously used.
 *
 * New code should use App\Models\View directly. This alias can be removed
 * once all references are migrated, but there's no rush.
 */
class PostView extends View
{
    /**
     * BC shim — old call site in StatsOverview etc. used PostView::recordView($post, ...)
     * with a Post first argument. View::recordView() takes a generic Model so
     * this delegation is safe.
     */
    public static function recordView(\Illuminate\Database\Eloquent\Model $subject, string $ip, ?int $userId = null, ?string $userAgent = null, ?string $referer = null): void
    {
        parent::recordView($subject, $ip, $userId, $userAgent, $referer);
    }
}
