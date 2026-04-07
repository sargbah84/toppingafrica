<?php

declare(strict_types=1);

use App\Models\Page;

if (! function_exists('template_url')) {
    /**
     * Resolve the public URL of the canonical published page that uses the
     * given template key. Used so internal links keep working when an admin
     * renames the slug of a built-in section like /trending or /creators.
     *
     * Returns the fallback ("/" by default) when no published page with that
     * template exists yet — for example before the seeding migration runs.
     */
    function template_url(string $template, string $fallback = '/'): string
    {
        $page = Page::byTemplate($template);

        if (! $page) {
            return $fallback;
        }

        return url('/' . $page->slug);
    }
}
