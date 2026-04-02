<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Repositories\PostRepository;

final class InternalLinkSuggester
{
    public function __construct(
        private readonly PostRepository $postRepository,
    ) {}

    public function suggest(string $content, string $topic): array
    {
        $suggestions = [];

        // Get published blog posts that might be relevant
        $posts = $this->postRepository->getRecent(20);

        foreach ($posts as $post) {
            $relevanceScore = $this->calculateRelevance($post, $topic, $content);

            if ($relevanceScore > 0.3) {
                $suggestions[] = [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'url' => route('blog.show', $post->slug),
                    'relevance' => $relevanceScore,
                    'type' => 'article',
                ];
            }
        }

        // Add category-based suggestions based on topic
        $categorySuggestions = $this->getCategorySuggestions($topic);
        $suggestions = array_merge($suggestions, $categorySuggestions);

        // Sort by relevance and limit
        usort($suggestions, fn ($a, $b) => $b['relevance'] <=> $a['relevance']);

        return array_slice($suggestions, 0, config('blog.ai.max_internal_links', 5));
    }

    private function calculateRelevance(Post $post, string $topic, string $content): float
    {
        $topicWords = $this->extractKeywords($topic);
        $titleWords = $this->extractKeywords($post->title);

        $matchCount = 0;
        $totalWords = count($topicWords);

        foreach ($topicWords as $word) {
            if (in_array($word, $titleWords) || stripos($post->excerpt ?? '', $word) !== false) {
                $matchCount++;
            }
        }

        return $totalWords > 0 ? $matchCount / $totalWords : 0;
    }

    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $words = explode(' ', $text);

        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'it', 'its', 'i', 'you', 'he', 'she', 'we', 'they', 'what', 'which', 'who', 'when', 'where', 'why', 'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very'];

        return array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
    }

    private function getCategorySuggestions(string $topic): array
    {
        $suggestions = [];
        $topic = strtolower($topic);

        $categories = [
            'africa-news' => [
                'name' => 'Africa News',
                'url' => '/category/africa-news',
                'keywords' => ['africa', 'african', 'continent', 'news', 'breaking', 'update', 'report'],
            ],
            'entertainment' => [
                'name' => 'Entertainment',
                'url' => '/category/entertainment',
                'keywords' => ['music', 'film', 'movie', 'nollywood', 'afrobeats', 'celebrity', 'entertainment', 'artist', 'concert', 'festival'],
            ],
            'business' => [
                'name' => 'Business & Economy',
                'url' => '/category/business',
                'keywords' => ['business', 'economy', 'startup', 'investment', 'trade', 'finance', 'market', 'entrepreneur', 'funding', 'gdp'],
            ],
            'technology' => [
                'name' => 'Technology',
                'url' => '/category/technology',
                'keywords' => ['tech', 'technology', 'digital', 'fintech', 'startup', 'innovation', 'ai', 'software', 'mobile', 'app'],
            ],
            'sports' => [
                'name' => 'Sports',
                'url' => '/category/sports',
                'keywords' => ['sports', 'football', 'soccer', 'afcon', 'athletics', 'basketball', 'cricket', 'rugby', 'olympics', 'player'],
            ],
            'health' => [
                'name' => 'Health & Wellness',
                'url' => '/category/health',
                'keywords' => ['health', 'wellness', 'medical', 'disease', 'hospital', 'vaccine', 'nutrition', 'fitness', 'mental health'],
            ],
            'politics' => [
                'name' => 'Politics & Governance',
                'url' => '/category/politics',
                'keywords' => ['politics', 'government', 'election', 'president', 'parliament', 'policy', 'democracy', 'leadership', 'governance'],
            ],
            'culture' => [
                'name' => 'Culture & Lifestyle',
                'url' => '/category/culture',
                'keywords' => ['culture', 'lifestyle', 'fashion', 'food', 'tradition', 'heritage', 'art', 'travel', 'tourism', 'diaspora'],
            ],
            'opinion' => [
                'name' => 'Opinion & Editorial',
                'url' => '/category/opinion',
                'keywords' => ['opinion', 'editorial', 'analysis', 'commentary', 'perspective', 'debate'],
            ],
        ];

        foreach ($categories as $slug => $data) {
            $matchScore = 0;
            $matchCount = 0;

            foreach ($data['keywords'] as $keyword) {
                if (stripos($topic, $keyword) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $matchScore = min(0.9, 0.4 + ($matchCount * 0.15));

                $suggestions[] = [
                    'title' => $data['name'],
                    'slug' => $slug,
                    'url' => $data['url'],
                    'relevance' => $matchScore,
                    'type' => 'category',
                ];
            }
        }

        return $suggestions;
    }
}
