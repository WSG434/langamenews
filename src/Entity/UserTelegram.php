<?php

namespace App\Entity;

use App\Repository\UserTelegramRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTelegramRepository::class)]
#[ORM\Table(name: 'user_telegram')]
class UserTelegram
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64, unique: true)]
    private string $linkToken;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $chatId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $linkedAt = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->linkToken = bin2hex(random_bytes(16));
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getLinkToken(): string { return $this->linkToken; }

    public function getChatId(): ?int { return $this->chatId; }
    public function isLinked(): bool { return $this->chatId !== null; }

    public function link(int $chatId): void
    {
        $this->chatId = $chatId;
        $this->linkedAt = new \DateTimeImmutable();
    }

    public function unlink(): void
    {
        $this->chatId = null;
        $this->linkedAt = null;
    }
}
