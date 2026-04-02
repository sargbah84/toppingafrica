<?php

declare(strict_types=1);

namespace App\Services\Blog\Seo;

use App\Models\Post;
use Illuminate\Support\Facades\Log;

final class UserEngagementAnalyzer
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('seo-intelligence', []);
    }

    public function analyze(string $content, ?int $postId = null): array
    {
        try {
            $scores = [];

            // 1. Internal links
            $internalLinkAnalysis = $this->analyzeInternalLinks($content, $postId);
            $scores['internal_links'] = $internalLinkAnalysis['score'];

            // 2. External links
            $externalLinkAnalysis = $this->analyzeExternalLinks($content);
            $scores['external_links'] = $externalLinkAnalysis['score'];

            // 3. Media presence
            $mediaAnalysis = $this->analyzeMedia($content);
            $scores['media_presence'] = $mediaAnalysis['score'];

            // 4. Call-to-action presence
            $ctaAnalysis = $this->analyzeCTA($content);
            $scores['cta_presence'] = $ctaAnalysis['score'];

            // 5. Lists and structured content
            $structureAnalysis = $this->analyzeStructuredContent($content);
            $scores['structured_content'] = $structureAnalysis['score'];

            $overallScore = (int) round(array_sum($scores) / count($scores));

            return [
                'score' => max(0, min(100, $overallScore)),
                'internal_links_count' => $internalLinkAnalysis['count'],
                'internal_links_target' => $internalLinkAnalysis['target'],
                'external_links_count' => $externalLinkAnalysis['count'],
                'external_links_target' => $externalLinkAnalysis['target'],
                'images_count' => $mediaAnalysis['images'],
                'videos_count' => $mediaAnalysis['videos'],
                'embeds_count' => $mediaAnalysis['embeds'],
                'has_cta' => $ctaAnalysis['found'],
                'cta_types' => $ctaAnalysis['types'],
                'lists_count' => $structureAnalysis['lists'],
                'tables_count' => $structureAnalysis['tables'],
                'sub_scores' => $scores,
            ];
        } catch (\Exception $e) {
            Log::warning('User engagement analysis failed', ['error' => $e->getMessage()]);
            return ['score' => 0, 'sub_scores' => []];
        }
    }

    private function analyzeInternalLinks(string $content, ?int $postId): array
    {
        $appUrl = config('app.url', '');
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?? '';

        preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*>/si', $content, $matches);

        $internalCount = 0;
        foreach ($matches[1] ?? [] as $url) {
            $urlHost = parse_url($url, PHP_URL_HOST);
            if ($urlHost === $appHost || $urlHost === null && str_starts_with($url, '/')) {
                $internalCount++;
            }
        }

        $targets = $this->config['targets']['internal_links'] ?? ['min' => 3, 'max' => 5];

        if ($internalCount >= $targets['min'] && $internalCount <= $targets['max']) {
            $score = 100;
        } elseif ($internalCount > $targets['max']) {
            $score = 80;
        } elseif ($internalCount >= 1) {
            $score = (int) round(($internalCount / $targets['min']) * 70);
        } else {
            $score = 0;
        }

        return [
            'score' => min(100, $score),
            'count' => $internalCount,
            'target' => $targets['min'] . '-' . $targets['max'],
        ];
    }

    private function analyzeExternalLinks(string $content): array
    {
        $appUrl = config('app.url', '');
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?? '';

        preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\']+)["\'][^>]*>/si', $content, $matches);

        $externalCount = 0;
        foreach ($matches[1] ?? [] as $url) {
            $urlHost = parse_url($url, PHP_URL_HOST);
            if ($urlHost !== null && $urlHost !== $appHost) {
                $externalCount++;
            }
        }

        $targets = $this->config['targets']['external_links'] ?? ['min' => 2, 'max' => 3];

        if ($externalCount >= $targets['min'] && $externalCount <= $targets['max']) {
            $score = 100;
        } elseif ($externalCount > $targets['max']) {
            $score = 70;
        } elseif ($externalCount >= 1) {
            $score = (int) round(($externalCount / $targets['min']) * 60);
        } else {
            $score = 20;
        }

        return [
            'score' => min(100, $score),
            'count' => $externalCount,
            'target' => $targets['min'] . '-' . $targets['max'],
        ];
    }

    private function analyzeMedia(string $content): array
    {
        // Count images
        preg_match_all('/<img[^>]*>/si', $content, $imgMatches);
        $images = count($imgMatches[0]);

        // Count videos
        preg_match_all('/<video[^>]*>|<iframe[^>]*(?:youtube|vimeo)[^>]*>/si', $content, $videoMatches);
        $videos = count($videoMatches[0]);

        // Count embeds
        preg_match_all('/<iframe[^>]*>|<embed[^>]*>|<object[^>]*>/si', $content, $embedMatches);
        $embeds = count($embedMatches[0]) - $videos; // Don't double count video iframes

        $totalMedia = $images + $videos + max(0, $embeds);

        if ($totalMedia >= 3) {
            $score = 100;
        } elseif ($totalMedia >= 2) {
            $score = 80;
        } elseif ($totalMedia >= 1) {
            $score = 60;
        } else {
            $score = 20;
        }

        return [
            'score' => $score,
            'images' => $images,
            'videos' => $videos,
            'embeds' => max(0, $embeds),
        ];
    }

    private function analyzeCTA(string $content): array
    {
        $lowerContent = strtolower(strip_tags($content));
        $ctaTypes = [];

        $ctaPatterns = [
            'subscribe' => '/subscribe|sign\s*up|join|newsletter/i',
            'comment' => '/leave\s*a\s*comment|tell\s*us|share\s*your|let\s*us\s*know|comment\s*below/i',
            'share' => '/share\s*this|share\s*on|spread\s*the\s*word/i',
            'read_more' => '/read\s*more|learn\s*more|check\s*out|discover|explore/i',
            'download' => '/download|get\s*your|grab\s*your|free\s*guide/i',
            'action' => '/click\s*here|try\s*(?:it|now|today)|get\s*started|start\s*(?:now|today)/i',
        ];

        foreach ($ctaPatterns as $type => $pattern) {
            if (preg_match($pattern, $content)) {
                $ctaTypes[] = $type;
            }
        }

        $found = !empty($ctaTypes);

        if (count($ctaTypes) >= 3) {
            $score = 100;
        } elseif (count($ctaTypes) >= 2) {
            $score = 80;
        } elseif (count($ctaTypes) >= 1) {
            $score = 60;
        } else {
            $score = 20;
        }

        return [
            'score' => $score,
            'found' => $found,
            'types' => $ctaTypes,
        ];
    }

    private function analyzeStructuredContent(string $content): array
    {
        // Count ordered/unordered lists
        preg_match_all('/<[ou]l[^>]*>/si', $content, $listMatches);
        $lists = count($listMatches[0]);

        // Count tables
        preg_match_all('/<table[^>]*>/si', $content, $tableMatches);
        $tables = count($tableMatches[0]);

        // Count blockquotes
        preg_match_all('/<blockquote[^>]*>/si', $content, $quoteMatches);
        $quotes = count($quoteMatches[0]);

        $totalStructured = $lists + $tables + $quotes;

        if ($totalStructured >= 3) {
            $score = 100;
        } elseif ($totalStructured >= 2) {
            $score = 80;
        } elseif ($totalStructured >= 1) {
            $score = 60;
        } else {
            $score = 30;
        }

        return [
            'score' => $score,
            'lists' => $lists,
            'tables' => $tables,
        ];
    }
}
