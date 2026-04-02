<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DataTransferObjects\Blog\PostGenerationRequest;

interface BlogGeneratorInterface
{
    public function generateBlogPost(PostGenerationRequest $request): array;

    public function generateSocialSharing(string $title, string $excerpt): array;

    public function getProviderName(): string;
}
