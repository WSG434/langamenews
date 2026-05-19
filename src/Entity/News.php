<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[ORM\Table(name: 'news')]
#[ORM\UniqueConstraint(name: 'uniq_source_uid', columns: ['source', 'source_uid'])]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 512)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 64)]
    private string $source;

    #[ORM\Column(length: 512)]
    private string $sourceUid;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $url;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $imageUrl;

    #[ORM\Column]
    private \DateTimeImmutable $fetchedAt;

    public function __construct(
        string $title,
        string $source,
        string $sourceUid,
        ?string $summary = null,
        ?string $content = null,
        ?\DateTimeImmutable $publishedAt = null,
        ?string $url = null,
        ?string $imageUrl = null,
    ) {
        $this->title = $title;
        $this->source = $source;
        $this->sourceUid = $sourceUid;
        $this->summary = $summary;
        $this->content = $content;
        $this->publishedAt = $publishedAt;
        $this->url = $url;
        $this->imageUrl = $imageUrl;
        $this->fetchedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getSummary(): ?string { return $this->summary; }
    public function getContent(): ?string { return $this->content; }
    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function getSource(): string { return $this->source; }
    public function getSourceUid(): string { return $this->sourceUid; }
    public function getUrl(): ?string { return $this->url; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function getFetchedAt(): \DateTimeImmutable { return $this->fetchedAt; }
}
