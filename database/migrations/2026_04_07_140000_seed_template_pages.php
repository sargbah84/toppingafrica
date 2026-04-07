<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Convert the previously hardcoded /trending and /creators routes into
     * template-backed CMS pages so they appear in the page editor and become
     * fully editable. This migration is idempotent — it skips any page that
     * already has the matching template, so re-running on existing data is safe.
     */
    public function up(): void
    {
        $seeds = [
            [
                'template' => 'trending',
                'title' => 'Trending',
                'slug' => 'trending',
                'meta_title' => 'Trending Across Africa Today',
                'meta_description' => 'Discover what\'s trending across Africa right now — music, business, sports, culture, tech and lifestyle.',
            ],
            [
                'template' => 'creators',
                'title' => 'Creators',
                'slug' => 'creators',
                'meta_title' => 'African Content Creators',
                'meta_description' => 'Discover rising and trending African content creators across comedy, fashion, food, music and more.',
            ],
        ];

        $createdPageIds = [];

        foreach ($seeds as $seed) {
            $existing = Page::where('template', $seed['template'])->first();
            if ($existing) {
                $createdPageIds[$seed['template']] = $existing->id;
                continue;
            }

            // If a page already squats on the desired slug (e.g. an admin made
            // one manually), pick an alternate slug so we don't collide.
            $slug = $seed['slug'];
            $suffix = 1;
            while (Page::where('slug', $slug)->exists()) {
                $slug = $seed['slug'] . '-' . (++$suffix);
            }

            $page = Page::create([
                'title' => $seed['title'],
                'slug' => $slug,
                'content' => '',
                'template' => $seed['template'],
                'status' => 'published',
                'order' => 0,
                'meta_title' => $seed['meta_title'],
                'meta_description' => $seed['meta_description'],
            ]);

            $createdPageIds[$seed['template']] = $page->id;
        }

        // Append the new template pages to header_pages so they appear in the
        // public top nav out of the box. We *append* (rather than overwrite)
        // so any existing admin-curated nav order is preserved. Pages already
        // present in the saved list are skipped.
        $existingRaw = json_decode(Setting::get('header_pages', '[]'), true) ?: [];
        $existing = [];
        foreach ($existingRaw as $entry) {
            if (is_int($entry) || (is_string($entry) && ctype_digit($entry))) {
                $existing[] = ['id' => (int) $entry, 'label' => null];
            } elseif (is_array($entry) && isset($entry['id'])) {
                $existing[] = [
                    'id' => (int) $entry['id'],
                    'label' => (isset($entry['label']) && trim((string) $entry['label']) !== '')
                        ? (string) $entry['label']
                        : null,
                ];
            }
        }

        $existingIds = array_column($existing, 'id');
        foreach (['trending', 'creators'] as $key) {
            if (! isset($createdPageIds[$key])) {
                continue;
            }
            if (in_array($createdPageIds[$key], $existingIds, true)) {
                continue;
            }
            $existing[] = ['id' => $createdPageIds[$key], 'label' => null];
            $existingIds[] = $createdPageIds[$key];
        }

        Setting::set('header_pages', json_encode($existing));
    }

    public function down(): void
    {
        // Intentionally non-destructive: leave the seeded pages in place
        // even if this migration is rolled back. Admins may have edited them.
    }
};
