<?php

namespace App\Notification;

class NotificationDispatcher
{
    /** @param iterable<NotificationSenderInterface> $senders */
    public function __construct(private readonly iterable $senders) {}

    public function dispatch(NotificationMessage $message): void
    {
        foreach ($this->senders as $sender) {
            $sender->send($message);
        }
    }
}
