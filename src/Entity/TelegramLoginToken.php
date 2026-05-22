<?php

namespace App\Entity;

use App\Repository\TelegramLoginTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TelegramLoginTokenRepository::class)]
#[ORM\Table(name: 'telegram_login_tokens')]
class TelegramLoginToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'bigint')]
    private int $chatId;

    #[ORM\Column(length: 6)]
    private string $code;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    public function __construct(int $chatId, string $code, int $ttlMinutes = 10)
    {
        $this->chatId = $chatId;
        $this->code = $code;
        $this->expiresAt = new \DateTimeImmutable("+{$ttlMinutes} minutes");
    }

    public function getId(): ?int { return $this->id; }
    public function getChatId(): int { return $this->chatId; }
    public function getCode(): string { return $this->code; }

    public function isExpired(): bool { return $this->expiresAt <= new \DateTimeImmutable(); }
    public function isUsed(): bool { return $this->usedAt !== null; }
    public function isValid(): bool { return !$this->isExpired() && !$this->isUsed(); }

    public function markUsed(): void { $this->usedAt = new \DateTimeImmutable(); }
}
