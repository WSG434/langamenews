<?php

namespace App\Entity;

use App\Repository\NewsSourceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsSourceRepository::class)]
#[ORM\Table(name: 'news_sources')]
class NewsSource
{
    public const TYPE_RSS = 'rss';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_RSS;

    #[ORM\Column(length: 512)]
    private string $url;

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastFetchedAt = null;

    public function __construct(string $code, string $name, string $url, string $type = self::TYPE_RSS)
    {
        $this->code = $code;
        $this->name = $name;
        $this->url = $url;
        $this->type = $type;
    }

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getUrl(): string { return $this->url; }
    public function isEnabled(): bool { return $this->enabled; }
    public function getLastFetchedAt(): ?\DateTimeImmutable { return $this->lastFetchedAt; }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function markFetched(): void
    {
        $this->lastFetchedAt = new \DateTimeImmutable();
    }
}
