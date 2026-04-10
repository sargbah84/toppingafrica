<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Blog;

final readonly class PostGenerationRequest
{
    public function __construct(
        public string $topic,
        public string $aiProvider = 'openai',
        public string $length = 'medium',
        public string $tone = 'professional',
        public ?string $targetKeyword = null,
        public string $postType = 'article',
        public array $additionalContext = [],
        public array $creatorIds = [],
    ) {}

    public function getWordCount(): int
    {
        return match ($this->length) {
            'short' => 800,
            'medium' => 1500,
            'long' => 2500,
            default => 1500,
        };
    }

    public function getToneDescription(): string
    {
        return match ($this->tone) {
            'professional' => 'Professional and authoritative',
            'conversational' => 'Friendly and conversational',
            'technical' => 'Technical and detailed',
            'beginner' => 'Beginner-friendly and educational',
            default => 'Professional and authoritative',
        };
    }

    public static function fromArray(array $data): self
    {
        return new self(
            topic: $data['topic'],
            aiProvider: $data['ai_provider'] ?? 'openai',
            length: $data['length'] ?? 'medium',
            tone: $data['tone'] ?? 'professional',
            targetKeyword: $data['target_keyword'] ?? null,
            additionalContext: $data['additional_context'] ?? [],
            creatorIds: $data['creator_ids'] ?? [],
        );
    }
}
