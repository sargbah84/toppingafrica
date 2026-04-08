<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Creator;

class CreatorSocialLinkBuilder
{
    /**
     * Build social links for a creator from a flexible data array.
     * Handles many key naming conventions Perplexity may return.
     */
    public function build(Creator $creator, array $data): void
    {
        $platforms = [
            'instagram' => [
                'handle_keys' => ['instagram_handle', 'instagram', 'instagram_username', 'ig_handle', 'ig'],
                'url_keys' => ['instagram_url', 'instagram_link'],
                'url_template' => 'https://instagram.com/{handle}',
            ],
            'tiktok' => [
                'handle_keys' => ['tiktok_handle', 'tiktok', 'tiktok_username', 'tt_handle'],
                'url_keys' => ['tiktok_url', 'tiktok_link'],
                'url_template' => 'https://tiktok.com/@{handle}',
            ],
            'youtube' => [
                'handle_keys' => ['youtube_handle', 'youtube_channel'],
                'url_keys' => ['youtube_channel_url', 'youtube_url', 'youtube', 'youtube_link'],
                'url_template' => 'https://youtube.com/@{handle}',
            ],
            'twitter' => [
                'handle_keys' => ['twitter_handle', 'twitter', 'twitter_username', 'x_handle', 'x'],
                'url_keys' => ['twitter_url', 'x_url'],
                'url_template' => 'https://x.com/{handle}',
            ],
            'facebook' => [
                'handle_keys' => ['facebook_handle', 'facebook_username'],
                'url_keys' => ['facebook_url', 'facebook', 'facebook_link'],
                'url_template' => 'https://facebook.com/{handle}',
            ],
            'website' => [
                'handle_keys' => [],
                'url_keys' => ['website_url', 'website', 'site', 'url'],
                'url_template' => null,
            ],
        ];

        // Some prompts return social_links as a nested array — flatten if present
        if (isset($data['social_links']) && is_array($data['social_links'])) {
            $data = array_merge($data, $data['social_links']);
        }

        foreach ($platforms as $platform => $config) {
            $handle = $this->firstNonEmpty($data, $config['handle_keys']);
            $url = $this->firstNonEmpty($data, $config['url_keys']);

            if (! $handle && ! $url) {
                continue;
            }

            // Strip leading @ if present
            if ($handle) {
                $handle = ltrim($handle, '@');
            }

            // Build URL from handle if no URL given
            if (! $url && $handle && $config['url_template']) {
                $url = str_replace('{handle}', $handle, $config['url_template']);
            }

            // If URL was given but no handle, try to extract handle from URL
            if ($url && ! $handle && $platform !== 'website') {
                $handle = $this->extractHandleFromUrl($url, $platform);
            }

            if ($url) {
                // Don't create duplicates for the same platform
                $creator->socialLinks()->updateOrCreate(
                    ['platform' => $platform],
                    ['url' => $url, 'handle' => $handle ?: null],
                );
            }
        }
    }

    private function firstNonEmpty(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return trim($data[$key]);
            }
        }

        return null;
    }

    private function extractHandleFromUrl(string $url, string $platform): ?string
    {
        // Use ~ as the regex delimiter because the character class [^/?#]
        // contains # — using # as the delimiter would terminate the pattern
        // prematurely and cause a "Unknown modifier ']'" error.
        $patterns = [
            'instagram' => '~instagram\.com/([^/?#]+)~i',
            'tiktok' => '~tiktok\.com/@?([^/?#]+)~i',
            'youtube' => '~youtube\.com/@?([^/?#]+)~i',
            'twitter' => '~(?:x|twitter)\.com/([^/?#]+)~i',
            'facebook' => '~facebook\.com/([^/?#]+)~i',
        ];

        if (! isset($patterns[$platform])) {
            return null;
        }

        if (preg_match($patterns[$platform], $url, $matches)) {
            return ltrim($matches[1], '@');
        }

        return null;
    }
}
