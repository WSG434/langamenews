<?php

namespace App\Notification\Sender;

use App\Entity\Notification;
use App\Notification\NotificationMessage;
use App\Notification\NotificationSenderInterface;
use App\Repository\NotificationRepository;

class SseNotificationSender implements NotificationSenderInterface
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    public function send(NotificationMessage $message): void
    {
        $this->notifications->save(new Notification($message->type, $message->payload));
    }
}
