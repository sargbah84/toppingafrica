<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Creator;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Finds reusable images already in our media library that match a search
 * query, scored by token overlap against filename, alt_text, photographer
 * credit, and (for creator photos) name + bio. Lets the agent reuse
 * curated images before reaching for Google/Pexels APIs.
 *
 * The score is normalized to 0..1 so callers can decide whether the
 * confidence is high enough to skip external lookup entirely (>=0.6 by
 * default) or merely worth keeping as a fallback (anything > 0).
 */
final class LibraryImageMatcher
{
    public const HIGH_CONFIDENCE_THRESHOLD = 0.6;

    /**
     * Tokens shorter than this are dropped from query/haystack alike.
     * Three chars catches "AI" / "DJ" / "ON" noise without losing names.
     */
    private const MIN_TOKEN_LENGTH = 3;

    /**
     * Words that aren't worth matching on — they appear on almost every
     * post so they'd inflate every score equally. Includes the country
     * itself since most ideas mention "Africa".
     */
    private const STOPWORDS = [
        'the', 'and', 'for', 'with', 'how', 'why', 'who', 'are', 'this', 'that',
        'from', 'into', 'over', 'across', 'about', 'their', 'they', 'them',
        'top', 'best', 'new', 'rising', 'these', 'what', 'when', 'which',
        'africa', 'african', 'africas', 'global', 'world',
    ];

    /**
     * @return array{media: Media, score: float, score_normalized: float, source: string}|null
     */
    public function findBest(string $query, array $excludeMediaIds = []): ?array
    {
        $results = $this->findCandidates($query, 1, $excludeMediaIds);

        return $results[0] ?? null;
    }

    /**
     * Best library match for a post — uses the post's title + focus
     * keyword + attached creator names as combined search text. Returns
     * the creator's profile photo immediately when the post already
     * has creators attached, because that's a stronger signal than any
     * text match.
     *
     * @return array{media: Media, score: float, score_normalized: float, source: string}|null
     */
    public function findBestForPost(Post $post, ?string $additionalQuery = null, array $excludeMediaIds = []): ?array
    {
        // Direct creator hit — if the agent already attached creators to
        // this post, prefer their profile photo over anything else.
        $attachedCreator = $post->creators()
            ->has('media')
            ->with(['media' => fn ($q) => $q->where('collection_name', 'profile_image')])
            ->first();
        if ($attachedCreator) {
            $media = $attachedCreator->media->first();
            if ($media && ! in_array($media->id, $excludeMediaIds, true)) {
                return [
                    'media' => $media,
                    'score' => 999.0,
                    'score_normalized' => 1.0,
                    'source' => 'library_creator_attached',
                ];
            }
        }

        $query = trim(implode(' ', array_filter([
            $additionalQuery,
            $post->title,
            $post->focus_keyword,
        ])));

        return $this->findBest($query, $excludeMediaIds);
    }

    /**
     * Return up to $limit ranked candidates, highest score first. Empty
     * array when the query has no usable tokens or nothing matches.
     *
     * @return array<int, array{media: Media, score: float, score_normalized: float, source: string}>
     */
    public function findCandidates(string $query, int $limit = 5, array $excludeMediaIds = []): array
    {
        $tokens = $this->tokenize($query);
        if ($tokens === []) {
            return [];
        }

        $creatorMatches = $this->scoreCreatorPhotos($tokens, $excludeMediaIds);
        $libraryMatches = $this->scoreLibraryMedia($tokens, $excludeMediaIds);

        $maxPossibleScore = max(1, count($tokens) * 4); // 4 = highest per-field weight

        $all = collect([...$creatorMatches, ...$libraryMatches])
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->take($limit)
            ->map(function (array $row) use ($maxPossibleScore): array {
                $row['score_normalized'] = min(1.0, $row['score'] / $maxPossibleScore);

                return $row;
            })
            ->all();

        return $all;
    }

    /**
     * Walk all candidates (highest score first), passing each to the
     * caller's reachability check. Returns the first that's reachable,
     * or null if every candidate failed. The caller owns reachability
     * because we don't want to hard-code Spatie's S3 detection here.
     *
     * @param  callable(Media): bool  $isReachable
     */
    public function findFirstReachable(string $query, callable $isReachable, int $maxCandidates = 5, array $excludeMediaIds = []): ?array
    {
        foreach ($this->findCandidates($query, $maxCandidates, $excludeMediaIds) as $candidate) {
            if ($isReachable($candidate['media'])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{media: Media, score: float, source: string}>
     */
    private function scoreCreatorPhotos(array $tokens, array $excludeMediaIds): array
    {
        // Find creators whose name overlaps at least one query token — much
        // smaller working set than loading every creator with media.
        $creators = Creator::query()
            ->has('media')
            ->where(function (Builder $q) use ($tokens): void {
                foreach ($tokens as $token) {
                    $q->orWhere('name', 'like', '%'.$token.'%');
                }
            })
            ->with(['media' => fn ($q) => $q->where('collection_name', 'profile_image')])
            ->limit(50)
            ->get();

        $rows = [];
        foreach ($creators as $creator) {
            $media = $creator->media->first();
            if (! $media || in_array($media->id, $excludeMediaIds, true)) {
                continue;
            }

            $haystack = [
                'name' => $this->tokenize($creator->name ?? ''),
                'bio' => $this->tokenize($creator->bio ?? ''),
                'category' => $this->tokenize((string) ($creator->category ?? '')),
            ];

            // Name hits are the strongest signal — weight 4 per token.
            $score = 4 * $this->countTokenOverlap($tokens, $haystack['name'])
                   + 2 * $this->countTokenOverlap($tokens, $haystack['bio'])
                   + 1 * $this->countTokenOverlap($tokens, $haystack['category']);

            if ($score > 0) {
                $rows[] = ['media' => $media, 'score' => (float) $score, 'source' => 'library_creator'];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{media: Media, score: float, source: string}>
     */
    private function scoreLibraryMedia(array $tokens, array $excludeMediaIds): array
    {
        // Two-phase: cheap SQL LIKE filter to narrow the 1k+ row table to a
        // working set, then in-PHP token scoring on the survivors.
        $query = Media::query()
            ->where('collection_name', 'default')
            ->whereNotIn('id', $excludeMediaIds);

        $query->where(function (Builder $q) use ($tokens): void {
            foreach ($tokens as $token) {
                $like = '%'.$token.'%';
                $q->orWhere('name', 'like', $like)
                    ->orWhere('file_name', 'like', $like)
                    ->orWhere('custom_properties', 'like', $like);
            }
        });

        $candidates = $query->limit(100)->get();

        $rows = [];
        foreach ($candidates as $media) {
            $custom = is_array($media->custom_properties) ? $media->custom_properties : [];

            $altTokens = $this->tokenize((string) ($custom['alt_text'] ?? ''));
            $nameTokens = $this->tokenize($this->stripExt($media->name ?? '').' '.$this->stripExt($media->file_name ?? ''));
            $photographerTokens = $this->tokenize((string) ($custom['photographer'] ?? ''));

            // alt_text from Google searches carries the source page's <title>
            // which is the richest signal we have — weight 3 per token.
            // Filename is often a slug of the original page title — weight 2.
            // Photographer credits help for stock photos — weight 1.
            $score = 3 * $this->countTokenOverlap($tokens, $altTokens)
                   + 2 * $this->countTokenOverlap($tokens, $nameTokens)
                   + 1 * $this->countTokenOverlap($tokens, $photographerTokens);

            if ($score > 0) {
                $rows[] = ['media' => $media, 'score' => (float) $score, 'source' => 'library_media'];
            }
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        if ($text === '') {
            return [];
        }

        // Strip HTML and JSON-encoded slashes/quotes so escaped alt_text
        // ("Burna Boy’s") still tokenises to "Burna Boy".
        $text = strip_tags($text);
        $text = (string) preg_replace('/[\\\\\\/\']/u', ' ', $text);
        $text = Str::of($text)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/u', ' ')
            ->squish()
            ->value();

        $tokens = array_filter(
            explode(' ', $text),
            fn (string $t) => strlen($t) >= self::MIN_TOKEN_LENGTH && ! in_array($t, self::STOPWORDS, true)
        );

        return array_values(array_unique($tokens));
    }

    private function countTokenOverlap(array $queryTokens, array $haystackTokens): int
    {
        if ($queryTokens === [] || $haystackTokens === []) {
            return 0;
        }

        return count(array_intersect($queryTokens, $haystackTokens));
    }

    private function stripExt(string $filename): string
    {
        $dot = strrpos($filename, '.');

        return $dot === false ? $filename : substr($filename, 0, $dot);
    }
}
