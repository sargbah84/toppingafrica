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

if (! function_exists('nofollow_ad_links')) {
    /**
     * Ensure every <a> tag inside a block of admin-supplied ad/banner HTML
     * carries rel="nofollow sponsored noopener".
     *
     * Ad and banner links (e.g. to external sponsor sites like Postigniter)
     * must not be counted by search engines as editorial backlinks, otherwise
     * the destination site's backlink profile looks artificially inflated and
     * can be penalised. This normalises the rel attribute regardless of how the
     * admin authored the raw HTML.
     */
    function nofollow_ad_links(string $html): string
    {
        return preg_replace_callback('/<a\b([^>]*)>/i', function ($m) {
            $attrs = $m[1];

            if (preg_match('/\brel\s*=\s*(["\'])(.*?)\1/i', $attrs, $rel)) {
                $tokens = array_filter(preg_split('/\s+/', trim($rel[2])));
                foreach (['nofollow', 'sponsored', 'noopener'] as $needed) {
                    if (! in_array($needed, $tokens, true)) {
                        $tokens[] = $needed;
                    }
                }
                $newRel = 'rel="' . implode(' ', $tokens) . '"';

                return '<a' . preg_replace('/\brel\s*=\s*(["\']).*?\1/i', $newRel, $attrs) . '>';
            }

            return '<a' . $attrs . ' rel="nofollow sponsored noopener">';
        }, $html) ?? $html;
    }
}
