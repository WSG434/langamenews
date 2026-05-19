<?php

namespace App\Entity;

use App\Repository\UserNotificationSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNotificationSettingRepository::class)]
#[ORM\Table(name: 'user_notification_settings')]
class UserNotificationSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private bool $telegramEnabled = true;

    public function __construct(User $user, bool $telegramEnabled = true)
    {
        $this->user = $user;
        $this->telegramEnabled = $telegramEnabled;
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function isTelegramEnabled(): bool { return $this->telegramEnabled; }

    public function setTelegramEnabled(bool $enabled): static
    {
        $this->telegramEnabled = $enabled;
        return $this;
    }
}
