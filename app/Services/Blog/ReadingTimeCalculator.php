<?php

declare(strict_types=1);

namespace App\Services\Blog;

final class ReadingTimeCalculator
{
    private int $wordsPerMinute;

    public function __construct()
    {
        $this->wordsPerMinute = config('blog.reading_speed', 200);
    }

    public function calculate(string $content): int
    {
        $text = $this->stripHtmlAndFormatting($content);
        $wordCount = str_word_count($text);
        $minutes = (int) ceil($wordCount / $this->wordsPerMinute);

        return max(1, $minutes);
    }

    public function getFormattedTime(string $content): string
    {
        $minutes = $this->calculate($content);

        return $minutes.' min read';
    }

    private function stripHtmlAndFormatting(string $content): string
    {
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
