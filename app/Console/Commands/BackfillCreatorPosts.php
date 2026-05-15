<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Creator;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackfillCreatorPosts extends Command
{
    protected $signature = 'creators:backfill-posts
        {--apply : Write matches to the post_creator pivot. Without this flag the command runs read-only.}
        {--post= : Limit to a single post by ID}
        {--creator= : Limit to a single creator by slug}
        {--since= : Only consider posts published on or after this date (Y-m-d)}
        {--min-mentions=3 : Minimum body mentions required for the body-mention signal}
        {--min-name-length=4 : Skip name-based signals for creators with names shorter than this}
        {--csv= : Write a CSV audit log to this path}';

    protected $description = 'Backfill post_creator links for existing posts using body links, title, body mentions, and tag-slug signals.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $minMentions = max(1, (int) $this->option('min-mentions'));
        $minNameLen = max(1, (int) $this->option('min-name-length'));
        $csvPath = $this->option('csv');

        $creators = $this->loadCreators();
        if ($creators->isEmpty()) {
            $this->warn('No creators found.');

            return self::SUCCESS;
        }

        $posts = $this->loadPosts();
        if ($posts->isEmpty()) {
            $this->warn('No posts to scan.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s mode | %d post(s) × %d creator(s)',
            $apply ? 'APPLY' : 'DRY-RUN',
            $posts->count(),
            $creators->count(),
        ));

        $csvHandle = null;
        if ($csvPath) {
            $csvHandle = fopen($csvPath, 'w');
            if ($csvHandle === false) {
                $this->error("Could not open CSV path: {$csvPath}");

                return self::FAILURE;
            }
            fputcsv($csvHandle, ['post_id', 'post_title', 'creator_id', 'creator_slug', 'signals', 'mention_count']);
        }

        $totalMatches = 0;
        $totalAttached = 0;
        $rows = [];

        foreach ($posts as $post) {
            $body = (string) ($post->content ?? '');
            $title = (string) ($post->title ?? '');
            $tagSlugs = $post->tags->pluck('slug')->all();

            foreach ($creators as $creator) {
                $signals = $this->detectSignals(
                    creator: $creator,
                    title: $title,
                    body: $body,
                    tagSlugs: $tagSlugs,
                    minMentions: $minMentions,
                    minNameLen: $minNameLen,
                );

                if (empty($signals['matched'])) {
                    continue;
                }

                $totalMatches++;

                $rows[] = [
                    'post_id' => $post->id,
                    'post_title' => $title,
                    'creator_id' => $creator->id,
                    'creator_slug' => $creator->slug,
                    'signals' => implode('+', $signals['matched']),
                    'mentions' => $signals['mention_count'],
                ];

                if ($csvHandle) {
                    fputcsv($csvHandle, [
                        $post->id,
                        $title,
                        $creator->id,
                        $creator->slug,
                        implode('+', $signals['matched']),
                        $signals['mention_count'],
                    ]);
                }

                if ($apply) {
                    // syncWithoutDetaching: additive, idempotent, never removes existing links.
                    $post->creators()->syncWithoutDetaching([$creator->id]);
                    $totalAttached++;
                }
            }
        }

        if ($csvHandle) {
            fclose($csvHandle);
            $this->line("CSV written to {$csvPath}");
        }

        if (! empty($rows)) {
            $this->table(
                ['Post', 'Title', 'Creator', 'Signals', 'Mentions'],
                array_map(fn ($r) => [
                    $r['post_id'],
                    \Illuminate\Support\Str::limit($r['post_title'], 50),
                    $r['creator_slug'],
                    $r['signals'],
                    $r['mentions'],
                ], array_slice($rows, 0, 100)),
            );

            if (count($rows) > 100) {
                $this->line(sprintf('... and %d more rows (see CSV for the full list).', count($rows) - 100));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%d match(es) found. %d attached (%s).',
            $totalMatches,
            $totalAttached,
            $apply ? 'applied' : 'dry-run — no DB writes',
        ));

        if (! $apply && $totalMatches > 0) {
            $this->comment('Re-run with --apply to write these matches.');
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Creator>
     */
    private function loadCreators(): Collection
    {
        $query = Creator::query()->where('status', 'published');

        if ($slug = $this->option('creator')) {
            $query->where('slug', $slug);
        }

        return $query->get(['id', 'name', 'slug']);
    }

    /**
     * @return Collection<int, Post>
     */
    private function loadPosts(): Collection
    {
        $query = Post::query()->with('tags:id,slug');

        if ($id = $this->option('post')) {
            $query->whereKey($id);
        }

        if ($since = $this->option('since')) {
            $query->where('published_at', '>=', $since);
        }

        return $query->get(['id', 'title', 'content', 'published_at']);
    }

    /**
     * @param  array<string>  $tagSlugs
     * @return array{matched: array<string>, mention_count: int}
     */
    private function detectSignals(
        Creator $creator,
        string $title,
        string $body,
        array $tagSlugs,
        int $minMentions,
        int $minNameLen,
    ): array {
        $matched = [];
        $mentionCount = 0;

        // 1. Internal link to /creators/{slug} — strongest signal.
        $linkPattern = '~/creators/' . preg_quote($creator->slug, '~') . '(?:[/?#"\']|$)~i';
        if (preg_match($linkPattern, $body)) {
            $matched[] = 'link';
        }

        // 4. Tag-slug match.
        if (in_array($creator->slug, $tagSlugs, true)) {
            $matched[] = 'tag';
        }

        $nameLong = strlen($creator->name) >= $minNameLen;

        // 2. Creator name in title (word-boundary, case-insensitive).
        if ($nameLong) {
            $namePattern = '/\b' . preg_quote($creator->name, '/') . '\b/i';
            if (preg_match($namePattern, $title)) {
                $matched[] = 'title';
            }

            // 3. Body mentions ≥ threshold.
            $stripped = strip_tags($body);
            $mentionCount = preg_match_all($namePattern, $stripped);
            if ($mentionCount >= $minMentions) {
                $matched[] = "body({$mentionCount})";
            }
        }

        return [
            'matched' => $matched,
            'mention_count' => $mentionCount,
        ];
    }
}
