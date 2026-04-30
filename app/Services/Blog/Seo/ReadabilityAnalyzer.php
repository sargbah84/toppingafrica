<?php

declare(strict_types=1);

namespace App\Services\Blog\Seo;

use Illuminate\Support\Facades\Log;

final class ReadabilityAnalyzer
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('seo-intelligence', []);
    }

    public function analyze(string $content, string $postType = 'article'): array
    {
        try {
            $plainText = $this->stripHtml($content);
            $isInteractive = in_array($postType, ['quiz', 'trivia', 'poll', 'video']);
            $scores = [];

            // 1. Flesch Reading Ease
            $fleschScore = $this->calculateFleschReadingEase($plainText);
            $scores['flesch_score'] = $this->scoreFleschReadingEase($fleschScore);

            // 2. Average sentence length
            $sentenceAnalysis = $this->analyzeSentenceLength($plainText);
            $scores['sentence_length'] = $sentenceAnalysis['score'];

            // 3. Average paragraph length — interactive types have short intros, don't penalize
            $paragraphAnalysis = $this->analyzeParagraphLength($content);
            $scores['paragraph_length'] = $isInteractive ? max(80, $paragraphAnalysis['score']) : $paragraphAnalysis['score'];

            // 4. Complex word percentage
            $complexWordAnalysis = $this->analyzeComplexWords($plainText);
            $scores['complex_words'] = $complexWordAnalysis['score'];

            // 5. Transition words — interactive types don't need transitions
            $transitionAnalysis = $this->analyzeTransitionWords($plainText);
            $scores['transition_words'] = $isInteractive ? max(70, $transitionAnalysis['score']) : $transitionAnalysis['score'];

            // 6. Passive voice detection
            $passiveAnalysis = $this->analyzePassiveVoice($plainText);
            $scores['passive_voice'] = $passiveAnalysis['score'];

            $overallScore = (int) round(array_sum($scores) / count($scores));

            return [
                'score' => max(0, min(100, $overallScore)),
                'flesch_score' => round($fleschScore, 1),
                'flesch_level' => $this->getFleschLevel($fleschScore),
                'avg_sentence_length' => $sentenceAnalysis['avg_length'],
                'sentence_count' => $sentenceAnalysis['count'],
                'avg_paragraph_length' => $paragraphAnalysis['avg_sentences'],
                'paragraph_count' => $paragraphAnalysis['count'],
                'complex_word_percentage' => $complexWordAnalysis['percentage'],
                'transition_word_percentage' => $transitionAnalysis['percentage'],
                'passive_voice_percentage' => $passiveAnalysis['percentage'],
                'sub_scores' => $scores,
            ];
        } catch (\Exception $e) {
            Log::warning('Readability analysis failed', ['error' => $e->getMessage()]);

            return ['score' => 0, 'sub_scores' => []];
        }
    }

    private function calculateFleschReadingEase(string $text): float
    {
        $words = str_word_count($text);
        $sentences = $this->countSentences($text);
        $syllables = $this->countSyllables($text);

        if ($words === 0 || $sentences === 0) {
            return 0;
        }

        $score = 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));

        return max(0, min(100, $score));
    }

    private function scoreFleschReadingEase(float $fleschScore): int
    {
        // Wider sweet spot: 60-80 is excellent for blog content.
        // Previously only 60-70 scored 100, causing AI overshoot to penalize itself.
        if ($fleschScore >= 60 && $fleschScore <= 80) {
            return 100;
        }

        if ($fleschScore >= 50 && $fleschScore < 60) {
            return 80;
        }

        if ($fleschScore > 80 && $fleschScore <= 90) {
            return 85; // Slightly too easy but still fine for web content
        }

        if ($fleschScore >= 40 && $fleschScore < 50) {
            return 60;
        }

        if ($fleschScore > 90) {
            return 70; // Very easy — may indicate too-simple language
        }

        if ($fleschScore >= 30 && $fleschScore < 40) {
            return 45;
        }

        return 30;
    }

    private function analyzeSentenceLength(string $text): array
    {
        $sentences = $this->splitSentences($text);
        $count = count($sentences);

        if ($count === 0) {
            return ['score' => 0, 'avg_length' => 0, 'count' => 0];
        }

        $totalWords = 0;
        foreach ($sentences as $sentence) {
            $totalWords += str_word_count($sentence);
        }

        $avgLength = round($totalWords / $count, 1);
        $targets = $this->config['targets']['avg_sentence_length'] ?? ['min' => 15, 'max' => 20];

        if ($avgLength >= $targets['min'] && $avgLength <= $targets['max']) {
            $score = 100;
        } elseif ($avgLength >= 10 && $avgLength < $targets['min']) {
            $score = 70;
        } elseif ($avgLength > $targets['max'] && $avgLength <= 25) {
            $score = 70;
        } elseif ($avgLength > 25) {
            $score = 40;
        } else {
            $score = 50;
        }

        return ['score' => $score, 'avg_length' => $avgLength, 'count' => $count];
    }

    private function analyzeParagraphLength(string $htmlContent): array
    {
        // Split by paragraph tags or double line breaks
        preg_match_all('/<p[^>]*>(.*?)<\/p>/si', $htmlContent, $matches);

        $paragraphs = $matches[1] ?? [];

        if (empty($paragraphs)) {
            // Fallback: split by double newlines
            $plainText = strip_tags($htmlContent);
            $paragraphs = preg_split('/\n\s*\n/', $plainText);
            $paragraphs = array_filter($paragraphs, fn ($p) => trim($p) !== '');
        }

        $count = count($paragraphs);

        if ($count === 0) {
            return ['score' => 30, 'avg_sentences' => 0, 'count' => 0];
        }

        $totalSentences = 0;
        foreach ($paragraphs as $paragraph) {
            $text = strip_tags($paragraph);
            $totalSentences += count($this->splitSentences($text));
        }

        $avgSentences = round($totalSentences / $count, 1);
        $targets = $this->config['targets']['avg_paragraph_length'] ?? ['min' => 3, 'max' => 5];

        if ($avgSentences >= $targets['min'] && $avgSentences <= $targets['max']) {
            $score = 100;
        } elseif ($avgSentences >= 2 && $avgSentences < $targets['min']) {
            $score = 70;
        } elseif ($avgSentences > $targets['max'] && $avgSentences <= 7) {
            $score = 60;
        } elseif ($avgSentences > 7) {
            $score = 40;
        } else {
            $score = 50;
        }

        return ['score' => $score, 'avg_sentences' => $avgSentences, 'count' => $count];
    }

    private function analyzeComplexWords(string $text): array
    {
        $words = preg_split('/\s+/', $text);
        $totalWords = count($words);

        if ($totalWords === 0) {
            return ['score' => 0, 'percentage' => 0];
        }

        $complexCount = 0;
        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z]/', '', $word);
            if ($this->countWordSyllables($word) >= 3) {
                $complexCount++;
            }
        }

        $percentage = round(($complexCount / $totalWords) * 100, 1);

        // Ideally < 15% complex words
        if ($percentage <= 10) {
            $score = 100;
        } elseif ($percentage <= 15) {
            $score = 80;
        } elseif ($percentage <= 20) {
            $score = 60;
        } elseif ($percentage <= 25) {
            $score = 40;
        } else {
            $score = 20;
        }

        return ['score' => $score, 'percentage' => $percentage];
    }

    private function analyzeTransitionWords(string $text): array
    {
        $transitions = [
            'however', 'therefore', 'furthermore', 'moreover', 'additionally',
            'consequently', 'meanwhile', 'nevertheless', 'nonetheless', 'similarly',
            'likewise', 'in addition', 'for example', 'for instance', 'in contrast',
            'on the other hand', 'as a result', 'in conclusion', 'in summary',
            'first', 'second', 'third', 'finally', 'next', 'then', 'also',
            'besides', 'indeed', 'certainly', 'above all', 'specifically',
            'in particular', 'notably', 'especially', 'significantly',
            'although', 'despite', 'even though', 'while', 'whereas',
            'because', 'since', 'due to', 'thus', 'hence',
        ];

        $sentences = $this->splitSentences($text);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return ['score' => 0, 'percentage' => 0];
        }

        // Count sentences that START with a transition word (not just contain one anywhere)
        $sentencesWithTransition = 0;
        foreach ($sentences as $sentence) {
            $trimmed = strtolower(trim($sentence));
            foreach ($transitions as $transition) {
                if (str_starts_with($trimmed, $transition.' ') || str_starts_with($trimmed, $transition.',')) {
                    $sentencesWithTransition++;
                    break;
                }
            }
        }

        $percentage = round(($sentencesWithTransition / $sentenceCount) * 100, 1);

        // Target: 25%+ of sentences should start with transitions
        // Relaxed from 30% since we now count sentence-initial only (more accurate)
        if ($percentage >= 25) {
            $score = 100;
        } elseif ($percentage >= 18) {
            $score = 80;
        } elseif ($percentage >= 10) {
            $score = 60;
        } elseif ($percentage >= 5) {
            $score = 45;
        } else {
            $score = 30;
        }

        return ['score' => $score, 'percentage' => $percentage];
    }

    private function analyzePassiveVoice(string $text): array
    {
        $sentences = $this->splitSentences($text);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return ['score' => 0, 'percentage' => 0];
        }

        // Common past participles that create false positives when paired with "is/are"
        // (they function as adjectives, not passive voice)
        $adjectivalParticiples = [
            'based', 'related', 'designed', 'dedicated', 'focused', 'interested',
            'concerned', 'involved', 'supposed', 'used', 'required', 'needed',
            'expected', 'allowed', 'known', 'considered', 'called', 'named',
            'located', 'connected', 'associated', 'equipped', 'prepared',
            'experienced', 'qualified', 'committed', 'determined', 'excited',
        ];
        $adjectivalStr = implode('|', $adjectivalParticiples);

        $passiveCount = 0;
        $passivePatterns = [
            // Core passive: "is/are/was/were + past participle" but exclude adjectival uses
            '/\b(was|were)\s+(?!'.$adjectivalStr.'\b)([\w]+ed|[\w]+en)\s+by\b/i',
            '/\b(is|are|was|were)\s+being\s+([\w]+ed|[\w]+en)\b/i',
            '/\b(has been|have been|had been)\s+([\w]+ed|[\w]+en)\b/i',
        ];

        foreach ($sentences as $sentence) {
            foreach ($passivePatterns as $pattern) {
                if (preg_match($pattern, $sentence)) {
                    $passiveCount++;
                    break;
                }
            }
        }

        $percentage = round(($passiveCount / $sentenceCount) * 100, 1);

        // Target: < 15% passive voice (relaxed -- technical/informational content naturally has some)
        if ($percentage <= 8) {
            $score = 100;
        } elseif ($percentage <= 15) {
            $score = 80;
        } elseif ($percentage <= 20) {
            $score = 60;
        } elseif ($percentage <= 25) {
            $score = 45;
        } else {
            $score = 25;
        }

        return ['score' => $score, 'percentage' => $percentage];
    }

    private function countSentences(string $text): int
    {
        return count($this->splitSentences($text));
    }

    private function splitSentences(string $text): array
    {
        $sentences = preg_split('/[.!?]+\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($sentences, fn ($s) => str_word_count(trim($s)) >= 3);
    }

    private function countSyllables(string $text): int
    {
        $words = preg_split('/\s+/', $text);
        $total = 0;

        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z]/', '', $word);
            $total += $this->countWordSyllables($word);
        }

        return $total;
    }

    private function countWordSyllables(string $word): int
    {
        $word = strtolower(trim($word));

        if (strlen($word) <= 3) {
            return 1;
        }

        $word = preg_replace('/(?:[^laeiouy]es|ed|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', $word);

        preg_match_all('/[aeiouy]{1,2}/', $word, $matches);

        return max(1, count($matches[0]));
    }

    private function getFleschLevel(float $score): string
    {
        if ($score >= 90) {
            return 'Very Easy';
        }
        if ($score >= 80) {
            return 'Easy';
        }
        if ($score >= 70) {
            return 'Fairly Easy';
        }
        if ($score >= 60) {
            return 'Standard';
        }
        if ($score >= 50) {
            return 'Fairly Difficult';
        }
        if ($score >= 30) {
            return 'Difficult';
        }

        return 'Very Difficult';
    }

    private function stripHtml(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
