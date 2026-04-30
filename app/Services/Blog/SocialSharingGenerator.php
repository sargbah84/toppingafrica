<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\DataTransferObjects\Blog\SocialSharingData;

final class SocialSharingGenerator
{
    public function generate(string $title, string $excerpt, array $tags = []): SocialSharingData
    {
        return new SocialSharingData(
            twitter: $this->generateTwitterPost($title, $tags),
            linkedin: $this->generateLinkedInPost($title, $excerpt),
        );
    }

    public function generateTwitterPost(string $title, array $tags = []): string
    {
        $maxLength = config('blog.social_sharing.twitter.max_length', 280);
        $hashtagCount = config('blog.social_sharing.twitter.hashtag_count', 3);

        $hashtags = $this->generateHashtags($tags, $hashtagCount);
        $hashtagString = implode(' ', $hashtags);

        $availableLength = $maxLength - strlen($hashtagString) - 5;

        $post = $title;
        if (strlen($post) > $availableLength) {
            $post = substr($post, 0, $availableLength - 3).'...';
        }

        return trim($post."\n\n".$hashtagString);
    }

    public function generateLinkedInPost(string $title, string $excerpt): string
    {
        $minLength = config('blog.social_sharing.linkedin.min_length', 150);
        $maxLength = config('blog.social_sharing.linkedin.max_length', 200);

        $hook = $this->createHook($title);
        $body = $this->truncateToWordBoundary($excerpt, $maxLength - strlen($hook) - 50);

        $post = $hook."\n\n".$body."\n\n".'Read more on Topping Africa!';

        if (strlen($post) < $minLength) {
            $post .= "\n\n#Africa #AfricanNews #ToppingAfrica";
        }

        return trim($post);
    }

    private function generateHashtags(array $tags, int $count): array
    {
        $hashtags = [];

        foreach (array_slice($tags, 0, $count) as $tag) {
            $hashtag = '#'.preg_replace('/[^a-zA-Z0-9]/', '', ucwords($tag));
            $hashtags[] = $hashtag;
        }

        $defaultHashtags = ['#Africa', '#AfricanNews', '#ToppingAfrica'];
        while (count($hashtags) < $count && ! empty($defaultHashtags)) {
            $hashtags[] = array_shift($defaultHashtags);
        }

        return array_slice($hashtags, 0, $count);
    }

    private function createHook(string $title): string
    {
        $hooks = [
            "Want to know the latest on {$title}?",
            "Here's what's happening: {$title}",
            "Curious about {$title}? Here's what you need to know:",
            "{$title} - A deep dive:",
        ];

        return $hooks[array_rand($hooks)];
    }

    private function truncateToWordBoundary(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $text = substr($text, 0, $maxLength);
        $lastSpace = strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return rtrim($text, '.,!?;:').'...';
    }
}
