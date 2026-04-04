<?php

declare(strict_types=1);

namespace App\Services\Blog\Seo;

use Illuminate\Support\Facades\Log;

final class TechnicalSeoAnalyzer
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('seo-intelligence', []);
    }

    public function analyze(string $content, ?string $metaTitle, ?string $metaDescription, string $slug, string $title, string $postType = 'article'): array
    {
        try {
            $scores = [];
            $isInteractive = in_array($postType, ['quiz', 'trivia', 'poll', 'video']);

            // 1. Meta title length
            $titleAnalysis = $this->analyzeMetaTitle($metaTitle, $title);
            $scores['meta_title'] = $titleAnalysis['score'];

            // 2. Meta description length
            $descAnalysis = $this->analyzeMetaDescription($metaDescription);
            $scores['meta_description'] = $descAnalysis['score'];

            // 3. H1 presence — interactive types don't need H1 in body content
            $h1Analysis = $this->analyzeH1($content);
            $scores['h1_tag'] = $isInteractive ? max(80, $h1Analysis['score']) : $h1Analysis['score'];

            // 4. Header hierarchy — interactive types don't need subheadings
            $headerAnalysis = $this->analyzeHeaderHierarchy($content);
            $scores['header_hierarchy'] = $isInteractive ? max(80, $headerAnalysis['score']) : $headerAnalysis['score'];

            // 5. URL slug quality
            $slugAnalysis = $this->analyzeSlug($slug);
            $scores['url_slug'] = $slugAnalysis['score'];

            // 6. Image alt text — interactive types don't need images in body
            $altTextAnalysis = $this->analyzeImageAltTexts($content);
            $scores['image_alt_text'] = $isInteractive ? max(70, $altTextAnalysis['score']) : $altTextAnalysis['score'];

            $overallScore = (int) round(array_sum($scores) / count($scores));

            return [
                'score' => max(0, min(100, $overallScore)),
                'meta_title_length' => $titleAnalysis['length'],
                'meta_title_status' => $titleAnalysis['status'],
                'meta_description_length' => $descAnalysis['length'],
                'meta_description_status' => $descAnalysis['status'],
                'h1_count' => $h1Analysis['count'],
                'h1_valid' => $h1Analysis['valid'],
                'header_hierarchy_valid' => $headerAnalysis['valid'],
                'header_issues' => $headerAnalysis['issues'],
                'headers_found' => $headerAnalysis['headers'],
                'slug_quality' => $slugAnalysis['quality'],
                'images_total' => $altTextAnalysis['total'],
                'images_with_alt' => $altTextAnalysis['with_alt'],
                'images_without_alt' => $altTextAnalysis['without_alt'],
                'sub_scores' => $scores,
            ];
        } catch (\Exception $e) {
            Log::warning('Technical SEO analysis failed', ['error' => $e->getMessage()]);
            return ['score' => 0, 'sub_scores' => []];
        }
    }

    private function analyzeMetaTitle(?string $metaTitle, string $postTitle): array
    {
        $title = $metaTitle ?: $postTitle;
        $length = mb_strlen($title);
        $targets = $this->config['targets']['meta_title_length'] ?? ['min' => 50, 'max' => 60];

        if ($length >= $targets['min'] && $length <= $targets['max']) {
            return ['score' => 100, 'length' => $length, 'status' => 'optimal'];
        }

        if ($length >= 40 && $length < $targets['min']) {
            return ['score' => 70, 'length' => $length, 'status' => 'slightly_short'];
        }

        if ($length > $targets['max'] && $length <= 70) {
            return ['score' => 70, 'length' => $length, 'status' => 'slightly_long'];
        }

        if ($length > 70) {
            return ['score' => 40, 'length' => $length, 'status' => 'too_long'];
        }

        if ($length < 30 && $length > 0) {
            return ['score' => 40, 'length' => $length, 'status' => 'too_short'];
        }

        if ($length === 0) {
            return ['score' => 0, 'length' => $length, 'status' => 'missing'];
        }

        return ['score' => 50, 'length' => $length, 'status' => 'acceptable'];
    }

    private function analyzeMetaDescription(?string $description): array
    {
        $length = mb_strlen($description ?? '');
        $targets = $this->config['targets']['meta_description_length'] ?? ['min' => 150, 'max' => 160];

        if ($length >= $targets['min'] && $length <= $targets['max']) {
            return ['score' => 100, 'length' => $length, 'status' => 'optimal'];
        }

        if ($length >= 120 && $length < $targets['min']) {
            return ['score' => 70, 'length' => $length, 'status' => 'slightly_short'];
        }

        if ($length > $targets['max'] && $length <= 200) {
            return ['score' => 60, 'length' => $length, 'status' => 'slightly_long'];
        }

        if ($length > 200) {
            return ['score' => 30, 'length' => $length, 'status' => 'too_long'];
        }

        if ($length > 0 && $length < 120) {
            return ['score' => 40, 'length' => $length, 'status' => 'too_short'];
        }

        if ($length === 0) {
            return ['score' => 0, 'length' => $length, 'status' => 'missing'];
        }

        return ['score' => 50, 'length' => $length, 'status' => 'acceptable'];
    }

    private function analyzeH1(string $content): array
    {
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/si', $content, $matches);
        $count = count($matches[0]);

        if ($count === 1) {
            return ['score' => 100, 'count' => 1, 'valid' => true];
        }

        if ($count === 0) {
            // The page title acts as H1, so content without H1 is acceptable
            return ['score' => 80, 'count' => 0, 'valid' => true];
        }

        // Multiple H1s is bad
        return ['score' => 40, 'count' => $count, 'valid' => false];
    }

    private function analyzeHeaderHierarchy(string $content): array
    {
        preg_match_all('/<(h[1-6])[^>]*>(.*?)<\/\1>/si', $content, $matches);

        if (empty($matches[1])) {
            return [
                'score' => 50,
                'valid' => true,
                'issues' => ['No headers found in content. Add headers to improve structure.'],
                'headers' => [],
            ];
        }

        $headers = [];
        $issues = [];
        $prevLevel = 0;

        foreach ($matches[1] as $i => $tag) {
            $level = (int) substr($tag, 1);
            $text = strip_tags($matches[2][$i]);
            $headers[] = ['level' => $level, 'text' => $text];

            // Check for hierarchy jumps (e.g., H2 -> H4 skipping H3)
            if ($prevLevel > 0 && $level > $prevLevel + 1) {
                $issues[] = "Header hierarchy jump: H{$prevLevel} to H{$level} (skipped H" . ($prevLevel + 1) . ")";
            }

            $prevLevel = $level;
        }

        $score = 100;
        $score -= count($issues) * 15;

        return [
            'score' => max(0, min(100, $score)),
            'valid' => empty($issues),
            'issues' => $issues,
            'headers' => $headers,
        ];
    }

    private function analyzeSlug(string $slug): array
    {
        $score = 100;
        $quality = 'good';

        // Check length
        if (strlen($slug) > 75) {
            $score -= 20;
            $quality = 'too_long';
        }

        // Check for numbers only
        if (preg_match('/^\d+$/', $slug)) {
            $score -= 30;
            $quality = 'not_descriptive';
        }

        // Check for uppercase
        if (preg_match('/[A-Z]/', $slug)) {
            $score -= 15;
            $quality = 'has_uppercase';
        }

        // Check for underscores (should use hyphens)
        if (strpos($slug, '_') !== false) {
            $score -= 10;
            $quality = 'uses_underscores';
        }

        // Check for stop words
        $stopWords = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was', 'one'];
        $slugWords = explode('-', $slug);
        $stopWordCount = count(array_intersect($slugWords, $stopWords));
        if ($stopWordCount > 2) {
            $score -= 10;
        }

        // Short descriptive slugs are good
        $wordCount = count($slugWords);
        if ($wordCount >= 3 && $wordCount <= 6) {
            $score = min(100, $score + 5);
        }

        return [
            'score' => max(0, min(100, $score)),
            'quality' => $score >= 80 ? 'good' : ($score >= 50 ? 'acceptable' : 'poor'),
        ];
    }

    private function analyzeImageAltTexts(string $content): array
    {
        preg_match_all('/<img[^>]*>/si', $content, $imgMatches);
        $total = count($imgMatches[0]);

        if ($total === 0) {
            return ['score' => 70, 'total' => 0, 'with_alt' => 0, 'without_alt' => 0];
        }

        $withAlt = 0;
        $withoutAlt = 0;

        foreach ($imgMatches[0] as $imgTag) {
            if (preg_match('/alt\s*=\s*["\']([^"\']+)["\']/i', $imgTag, $altMatch)) {
                if (trim($altMatch[1]) !== '') {
                    $withAlt++;
                } else {
                    $withoutAlt++;
                }
            } else {
                $withoutAlt++;
            }
        }

        $score = $total > 0 ? (int) round(($withAlt / $total) * 100) : 70;

        return [
            'score' => $score,
            'total' => $total,
            'with_alt' => $withAlt,
            'without_alt' => $withoutAlt,
        ];
    }
}
