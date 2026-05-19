<?php

namespace App\Notification\Sender;

use App\Notification\NotificationMessage;
use App\Notification\NotificationSenderInterface;
use App\Service\Telegram\TelegramSender;
use Psr\Log\LoggerInterface;

class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private readonly TelegramSender $telegram,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled = true,
    ) {}

    public function send(NotificationMessage $message): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $this->telegram->send($this->format($message), ['notification_type' => $message->type]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send Telegram notification', [
                'type' => $message->type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function format(NotificationMessage $message): string
    {
        return match ($message->type) {
            'user_registered' => "New user registered: {$message->payload['email']}",
            'user_logged_in' => "User logged in: {$message->payload['email']}",
            'news_imported' => "Imported {$message->payload['count']} news from {$message->payload['source']}",
            default => "Notification [{$message->type}]: " . json_encode($message->payload),
        };
    }
}
