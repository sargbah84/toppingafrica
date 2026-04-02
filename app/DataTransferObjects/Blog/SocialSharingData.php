<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Blog;

final readonly class SocialSharingData
{
    public function __construct(
        public string $twitter,
        public string $linkedin,
        public ?string $facebook = null,
    ) {}

    public function toArray(): array
    {
        return [
            'twitter' => $this->twitter,
            'linkedin' => $this->linkedin,
            'facebook' => $this->facebook,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            twitter: $data['twitter'],
            linkedin: $data['linkedin'],
            facebook: $data['facebook'] ?? null,
        );
    }
}
