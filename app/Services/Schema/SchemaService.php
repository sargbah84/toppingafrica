<?php

declare(strict_types=1);

namespace App\Services\Schema;

use App\Models\Post;

final class SchemaService
{
    /**
     * Generate Organization schema.
     *
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $config = config('schema.organization');
        $socialProfiles = collect($config['social_profiles'] ?? [])
            ->filter()
            ->values()
            ->toArray();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $config['name'],
            'legalName' => $config['legal_name'],
            'url' => $config['url'],
            'logo' => $config['logo'],
            'description' => $config['description'],
            'foundingDate' => $config['founding_date'],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'email' => $config['contact_email'],
                'contactType' => 'customer support',
            ],
            'sameAs' => $socialProfiles,
        ];
    }

    /**
     * Generate WebSite schema with search action.
     *
     * @return array<string, mixed>
     */
    public function website(): array
    {
        $config = config('schema.website');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $config['name'],
            'alternateName' => $config['alternate_name'],
            'url' => config('schema.organization.url'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $config['search_url_template'],
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Generate Article schema for a blog post.
     *
     * @return array<string, mixed>
     */
    public function article(Post $post): array
    {
        $publisher = config('schema.publisher');

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
                'name' => $publisher['name'],
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $publisher['logo'],
                ],
                'url' => $publisher['url'],
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
     * @param  array<int, array{name: string, url: string}>  $items
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
