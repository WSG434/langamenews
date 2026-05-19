<?php

namespace App\Notification;

interface NotificationSenderInterface
{
    public function send(NotificationMessage $message): void;
}
