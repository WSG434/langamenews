<?php

namespace App\News\Dto;

final readonly class NewsItemDto
{
    public function __construct(
        public string $title,
        public string $sourceUid,
        public ?string $summary = null,
        public ?string $content = null,
        public ?\DateTimeImmutable $publishedAt = null,
    ) {}
}
