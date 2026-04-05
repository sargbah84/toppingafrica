<?php

declare(strict_types=1);

namespace App\Services\Schema;

use App\Models\Post;
use App\Models\Setting;

final class SchemaService
{
    private function siteName(): string
    {
        return Setting::get('site_name', config('app.name'));
    }

    public function organization(): array
    {
        $siteName = $this->siteName();
        $siteUrl = config('app.url');

        $socialProfiles = collect([
            Setting::get('social_facebook'),
            Setting::get('social_twitter'),
            Setting::get('social_instagram'),
            Setting::get('social_youtube'),
            Setting::get('social_linkedin'),
        ])->filter()->values()->toArray();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'legalName' => $siteName,
            'url' => $siteUrl,
            'logo' => $siteUrl . '/images/logo.png',
            'description' => Setting::get('site_description', $siteName),
            'foundingDate' => config('schema.organization.founding_date', '2024'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'email' => Setting::get('contact_email', config('schema.organization.contact_email')),
                'contactType' => 'customer support',
            ],
            'sameAs' => $socialProfiles,
        ];
    }

    public function website(): array
    {
        $siteName = $this->siteName();
        $siteUrl = config('app.url');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'alternateName' => $siteName,
            'url' => $siteUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function article(Post $post): array
    {
        $siteName = $this->siteName();
        $siteUrl = config('app.url');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->meta_title ?? $post->title,
            'description' => $post->meta_description ?? $post->excerpt,
            'url' => route('blog.show', $post->slug),
            'datePublished' => ($post->published_at ?? $post->created_at)->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('blog.show', $post->slug),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteUrl . '/images/logo.png',
                ],
                'url' => $siteUrl,
            ],
        ];

        if ($post->author) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $post->author->name,
            ];
        }

        if ($post->featured_image_url) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $post->featured_image_url,
            ];
        }

        if ($post->tags && $post->tags->isNotEmpty()) {
            $schema['keywords'] = $post->tags->pluck('name')->implode(', ');
        }

        if ($post->categories && $post->categories->isNotEmpty()) {
            $schema['articleSection'] = $post->categories->first()->name;
        }

        $schema['wordCount'] = str_word_count(strip_tags((string) $post->content));

        return $schema;
    }

    /**
     * Generate NewsArticle schema variant for news content.
     *
     * @return array<string, mixed>
     */
    public function newsArticle(Post $post): array
    {
        $schema = $this->article($post);
        $schema['@type'] = 'NewsArticle';

        return $schema;
    }

    /**
     * Generate BreadcrumbList schema.
     *
     * @param array<int, array{name: string, url: string}> $items
     * @return array<string, mixed>
     */
    public function breadcrumbList(array $items): array
    {
        $listItems = [];
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }
}
