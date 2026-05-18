<?php

namespace App\Entity;

use App\Repository\ConfirmationCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfirmationCodeRepository::class)]
#[ORM\Table(name: 'confirmation_codes')]
class ConfirmationCode
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CONFIRMED = 'confirmed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 6)]
    private string $code;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private int $attempts = 0;

    public function __construct(User $user, string $code, int $ttlMinutes = 15)
    {
        $this->user = $user;
        $this->code = $code;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable("+{$ttlMinutes} minutes");
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getCode(): string { return $this->code; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getSentAt(): ?\DateTimeImmutable { return $this->sentAt; }
    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getAttempts(): int { return $this->attempts; }

    public function markSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function markFailed(): void
    {
        $this->status = self::STATUS_FAILED;
    }

    public function markConfirmed(): void
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }
}
