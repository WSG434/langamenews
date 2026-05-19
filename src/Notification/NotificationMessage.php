<?php

namespace App\Notification;

final readonly class NotificationMessage
{
    public function __construct(
        public string $type,
        public array $payload = [],
    ) {}
}
