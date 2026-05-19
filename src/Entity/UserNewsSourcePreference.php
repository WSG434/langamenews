<?php

namespace App\Entity;

use App\Repository\UserNewsSourcePreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNewsSourcePreferenceRepository::class)]
#[ORM\Table(name: 'user_news_source_preferences')]
#[ORM\UniqueConstraint(columns: ['user_id', 'news_source_id'])]
class UserNewsSourcePreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private NewsSource $newsSource;

    #[ORM\Column]
    private bool $enabled = true;

    public function __construct(User $user, NewsSource $newsSource, bool $enabled = true)
    {
        $this->user = $user;
        $this->newsSource = $newsSource;
        $this->enabled = $enabled;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getNewsSource(): NewsSource { return $this->newsSource; }
    public function isEnabled(): bool { return $this->enabled; }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }
}
