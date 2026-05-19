<?php

namespace App\Entity;

use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteSettingsRepository::class)]
#[ORM\Table(name: 'site_settings')]
class SiteSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $telegramEnabled = true;

    public function getId(): ?int { return $this->id; }
    public function isTelegramEnabled(): bool { return $this->telegramEnabled; }

    public function setTelegramEnabled(bool $enabled): static
    {
        $this->telegramEnabled = $enabled;
        return $this;
    }
}
