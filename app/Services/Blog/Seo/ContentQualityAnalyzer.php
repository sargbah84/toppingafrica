<?php

declare(strict_types=1);

namespace App\Services\Blog\Seo;

use Illuminate\Support\Facades\Log;

final class ContentQualityAnalyzer
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('seo-intelligence', []);
    }

    public function analyze(string $content, ?string $focusKeyword): array
    {
        try {
            $plainText = $this->stripHtml($content);
            $wordCount = str_word_count($plainText);
            $scores = [];

            // 1. Keyword placement & density
            $keywordAnalysis = $this->analyzeKeywordUsage($plainText, $content, $focusKeyword);
            $scores['keyword_usage'] = $keywordAnalysis['score'];

            // 2. Content length
            $lengthScore = $this->scorContentLength($wordCount);
            $scores['content_length'] = $lengthScore;

            // 3. Keyword stuffing detection
            $stuffingAnalysis = $this->detectKeywordStuffing($plainText, $focusKeyword);
            $scores['keyword_stuffing'] = $stuffingAnalysis['score'];

            // 4. Semantic keyword variations (LSI)
            $lsiScore = $this->analyzeLsiKeywords($plainText, $focusKeyword);
            $scores['lsi_keywords'] = $lsiScore;

            // 5. Content relevance
            $relevanceScore = $this->analyzeContentRelevance($plainText, $content, $focusKeyword);
            $scores['content_relevance'] = $relevanceScore;

            $overallScore = (int) round(array_sum($scores) / count($scores));

            return [
                'score' => max(0, min(100, $overallScore)),
                'word_count' => $wordCount,
                'keyword_density' => $keywordAnalysis['density'],
                'keyword_count' => $keywordAnalysis['count'],
                'keyword_in_first_paragraph' => $keywordAnalysis['in_first_paragraph'],
                'keyword_in_headings' => $keywordAnalysis['in_headings'],
                'keyword_stuffing_detected' => $stuffingAnalysis['detected'],
                'sub_scores' => $scores,
            ];
        } catch (\Exception $e) {
            Log::warning('Content quality analysis failed', ['error' => $e->getMessage()]);
            return ['score' => 0, 'word_count' => 0, 'sub_scores' => []];
        }
    }

    private function analyzeKeywordUsage(string $plainText, string $html, ?string $keyword): array
    {
        if (empty($keyword)) {
            return [
                'score' => 30,
                'density' => 0,
                'count' => 0,
                'in_first_paragraph' => false,
                'in_headings' => false,
            ];
        }

        $keyword = strtolower(trim($keyword));
        $lowerText = strtolower($plainText);
        $wordCount = str_word_count($plainText);

        // Count keyword occurrences
        $count = substr_count($lowerText, $keyword);
        $density = $wordCount > 0 ? ($count * str_word_count($keyword) / $wordCount) * 100 : 0;

        // Check first paragraph
        $firstParagraph = $this->getFirstParagraph($html);
        $inFirstParagraph = stripos($firstParagraph, $keyword) !== false;

        // Check headings
        preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/si', $html, $headingMatches);
        $headingsText = strtolower(implode(' ', $headingMatches[1] ?? []));
        $inHeadings = stripos($headingsText, $keyword) !== false;

        // Score
        $score = 0;
        $targets = $this->config['targets']['keyword_density'] ?? ['min' => 1.0, 'max' => 2.0];

        // Density scoring (40 points)
        if ($density >= $targets['min'] && $density <= $targets['max']) {
            $score += 40;
        } elseif ($density > 0 && $density < $targets['min']) {
            $score += (int) round(($density / $targets['min']) * 30);
        } elseif ($density > $targets['max'] && $density <= 3.0) {
            $score += 25;
        } else {
            $score += 10;
        }

        // First paragraph (20 points)
        if ($inFirstParagraph) {
            $score += 20;
        }

        // Headings (20 points)
        if ($inHeadings) {
            $score += 20;
        }

        // Presence at all (20 points)
        if ($count > 0) {
            $score += 20;
        }

        return [
            'score' => min(100, $score),
            'density' => round($density, 2),
            'count' => $count,
            'in_first_paragraph' => $inFirstParagraph,
            'in_headings' => $inHeadings,
        ];
    }

    private function scorContentLength(int $wordCount): int
    {
        $targets = $this->config['targets']['word_count'] ?? ['min' => 1500, 'max' => 2500];

        if ($wordCount >= $targets['min'] && $wordCount <= $targets['max']) {
            return 100;
        }

        if ($wordCount > $targets['max']) {
            // Slightly penalize very long content
            $excess = $wordCount - $targets['max'];
            return max(60, 100 - (int) round($excess / 100));
        }

        if ($wordCount >= 1000) {
            return 70;
        }

        if ($wordCount >= 500) {
            return 50;
        }

        if ($wordCount >= 300) {
            return 30;
        }

        return 15;
    }

    private function detectKeywordStuffing(string $text, ?string $keyword): array
    {
        if (empty($keyword)) {
            return ['score' => 100, 'detected' => false];
        }

        $lowerText = strtolower($text);
        $keyword = strtolower($keyword);
        $wordCount = str_word_count($text);

        if ($wordCount === 0) {
            return ['score' => 100, 'detected' => false];
        }

        $count = substr_count($lowerText, $keyword);
        $density = ($count * str_word_count($keyword) / $wordCount) * 100;

        if ($density > 3.0) {
            return ['score' => 20, 'detected' => true];
        }

        if ($density > 2.5) {
            return ['score' => 50, 'detected' => false];
        }

        return ['score' => 100, 'detected' => false];
    }

    private function analyzeLsiKeywords(string $text, ?string $keyword): int
    {
        if (empty($keyword)) {
            return 50;
        }

        // Check for semantic variations / related word forms
        $keyword = strtolower($keyword);
        $text = strtolower($text);
        $variations = 0;

        // Plural/singular variations
        if (stripos($text, $keyword . 's') !== false || stripos($text, rtrim($keyword, 's')) !== false) {
            $variations++;
        }

        // -ing form
        if (stripos($text, $keyword . 'ing') !== false) {
            $variations++;
        }

        // -ed form
        if (stripos($text, $keyword . 'ed') !== false) {
            $variations++;
        }

        // Synonym detection via word parts
        $keywordParts = explode(' ', $keyword);
        $partMatches = 0;
        foreach ($keywordParts as $part) {
            if (strlen($part) > 3 && substr_count($text, $part) > 1) {
                $partMatches++;
            }
        }

        $variationScore = min(100, ($variations * 20) + ($partMatches * 15) + 30);

        return $variationScore;
    }

    private function analyzeContentRelevance(string $plainText, string $html, ?string $keyword): int
    {
        if (empty($keyword)) {
            return 50;
        }

        $keyword = strtolower($keyword);
        $text = strtolower($plainText);

        // Split content into sections
        $sections = preg_split('/<h[1-6]/i', $html);
        $totalSections = max(1, count($sections));
        $sectionsWithKeyword = 0;

        foreach ($sections as $section) {
            if (stripos(strip_tags($section), $keyword) !== false) {
                $sectionsWithKeyword++;
            }
        }

        $distribution = $sectionsWithKeyword / $totalSections;

        // Good distribution: keyword appears in 30-70% of sections
        if ($distribution >= 0.3 && $distribution <= 0.7) {
            return 100;
        }

        if ($distribution >= 0.1) {
            return 70;
        }

        if ($distribution > 0) {
            return 50;
        }

        return 20;
    }

    private function getFirstParagraph(string $html): string
    {
        if (preg_match('/<p[^>]*>(.*?)<\/p>/si', $html, $matches)) {
            return strip_tags($matches[1]);
        }

        // Fallback: first 200 chars
        return substr(strip_tags($html), 0, 200);
    }

    private function stripHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
