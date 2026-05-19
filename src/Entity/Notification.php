<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
class Notification
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\Column(length: 64)]
    private string $type;

    #[ORM\Column(type: Types::JSON)]
    private array $payload;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $type, array $payload = [])
    {
        $this->type = $type;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getPayload(): array { return $this->payload; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
