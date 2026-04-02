<?php

declare(strict_types=1);

namespace App\Services\Blog\Seo;

use Illuminate\Support\Facades\Log;

final class OnPageElementsAnalyzer
{
    public function analyze(string $content, ?string $featuredImage, ?string $excerpt): array
    {
        try {
            $scores = [];

            // 1. Featured image
            $featuredImageAnalysis = $this->analyzeFeaturedImage($featuredImage);
            $scores['featured_image'] = $featuredImageAnalysis['score'];

            // 2. Image count and alt text compliance
            $imageAnalysis = $this->analyzeImages($content);
            $scores['image_quality'] = $imageAnalysis['score'];

            // 3. Content formatting (bold, italics, lists)
            $formattingAnalysis = $this->analyzeFormatting($content);
            $scores['formatting'] = $formattingAnalysis['score'];

            // 4. Content structure
            $structureAnalysis = $this->analyzeContentStructure($content);
            $scores['content_structure'] = $structureAnalysis['score'];

            // 5. Excerpt/summary
            $excerptAnalysis = $this->analyzeExcerpt($excerpt);
            $scores['excerpt'] = $excerptAnalysis['score'];

            $overallScore = (int) round(array_sum($scores) / count($scores));

            return [
                'score' => max(0, min(100, $overallScore)),
                'has_featured_image' => $featuredImageAnalysis['exists'],
                'image_count' => $imageAnalysis['count'],
                'images_with_alt' => $imageAnalysis['with_alt'],
                'has_bold' => $formattingAnalysis['has_bold'],
                'has_italics' => $formattingAnalysis['has_italics'],
                'has_lists' => $formattingAnalysis['has_lists'],
                'has_headers' => $structureAnalysis['has_headers'],
                'header_count' => $structureAnalysis['header_count'],
                'has_excerpt' => $excerptAnalysis['exists'],
                'excerpt_length' => $excerptAnalysis['length'],
                'sub_scores' => $scores,
            ];
        } catch (\Exception $e) {
            Log::warning('On-page elements analysis failed', ['error' => $e->getMessage()]);
            return ['score' => 0, 'sub_scores' => []];
        }
    }

    private function analyzeFeaturedImage(?string $featuredImage): array
    {
        $exists = !empty($featuredImage);

        return [
            'score' => $exists ? 100 : 0,
            'exists' => $exists,
        ];
    }

    private function analyzeImages(string $content): array
    {
        preg_match_all('/<img[^>]*>/si', $content, $imgMatches);
        $count = count($imgMatches[0]);

        if ($count === 0) {
            return ['score' => 30, 'count' => 0, 'with_alt' => 0];
        }

        $withAlt = 0;
        foreach ($imgMatches[0] as $imgTag) {
            if (preg_match('/alt\s*=\s*["\']([^"\']+)["\']/i', $imgTag, $altMatch)) {
                if (trim($altMatch[1]) !== '') {
                    $withAlt++;
                }
            }
        }

        $altCompliance = ($withAlt / $count) * 100;

        // Score: combination of image count (50%) and alt text compliance (50%)
        $countScore = match (true) {
            $count >= 3 => 50,
            $count >= 2 => 45,
            $count >= 1 => 35,
            default => 0,
        };
        $altScore = (int) round($altCompliance * 0.5);

        return [
            'score' => min(100, $countScore + $altScore),
            'count' => $count,
            'with_alt' => $withAlt,
        ];
    }

    private function analyzeFormatting(string $content): array
    {
        $hasBold = (bool) preg_match('/<(strong|b)[^>]*>/i', $content);
        $hasItalics = (bool) preg_match('/<(em|i)[^>]*>/i', $content);
        $hasLists = (bool) preg_match('/<[ou]l[^>]*>/i', $content);

        $formattingCount = ($hasBold ? 1 : 0) + ($hasItalics ? 1 : 0) + ($hasLists ? 1 : 0);

        if ($formattingCount >= 3) {
            $score = 100;
        } elseif ($formattingCount === 2) {
            $score = 75;
        } elseif ($formattingCount === 1) {
            $score = 50;
        } else {
            $score = 20;
        }

        return [
            'score' => $score,
            'has_bold' => $hasBold,
            'has_italics' => $hasItalics,
            'has_lists' => $hasLists,
        ];
    }

    private function analyzeContentStructure(string $content): array
    {
        preg_match_all('/<h[2-6][^>]*>/si', $content, $headerMatches);
        $headerCount = count($headerMatches[0]);

        $hasHeaders = $headerCount > 0;

        if ($headerCount >= 4) {
            $score = 100;
        } elseif ($headerCount >= 3) {
            $score = 85;
        } elseif ($headerCount >= 2) {
            $score = 70;
        } elseif ($headerCount >= 1) {
            $score = 50;
        } else {
            $score = 20;
        }

        return [
            'score' => $score,
            'has_headers' => $hasHeaders,
            'header_count' => $headerCount,
        ];
    }

    private function analyzeExcerpt(?string $excerpt): array
    {
        $exists = !empty(trim($excerpt ?? ''));
        $length = mb_strlen(trim($excerpt ?? ''));

        if (!$exists) {
            return ['score' => 0, 'exists' => false, 'length' => 0];
        }

        if ($length >= 100 && $length <= 200) {
            $score = 100;
        } elseif ($length >= 50) {
            $score = 70;
        } else {
            $score = 40;
        }

        return [
            'score' => $score,
            'exists' => $exists,
            'length' => $length,
        ];
    }
}
