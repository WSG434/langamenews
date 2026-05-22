<?php

namespace App\Notification\Sender;

use App\Notification\NotificationMessage;
use App\Notification\NotificationSenderInterface;
use App\Repository\SiteSettingsRepository;
use App\Repository\UserNotificationSettingRepository;
use App\Repository\UserTelegramRepository;
use App\Service\Telegram\TelegramSender;
use Psr\Log\LoggerInterface;

class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private readonly TelegramSender $telegram,
        private readonly LoggerInterface $logger,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly UserNotificationSettingRepository $userNotificationSettingRepository,
        private readonly UserTelegramRepository $userTelegramRepository,
        private readonly bool $enabled = true,
    ) {}

    public function send(NotificationMessage $message): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->siteSettingsRepository->getCurrent()->isTelegramEnabled()) {
            return;
        }

        if (isset($message->payload['userId'])) {
            $userSetting = $this->userNotificationSettingRepository->findForUserId($message->payload['userId']);
            if ($userSetting !== null && !$userSetting->isTelegramEnabled()) {
                return;
            }
        }

        try {
            $text = $this->format($message);
            $userId = $message->payload['userId'] ?? null;
            $isUserEvent = in_array($message->type, ['user_registered', 'user_logged_in'], true);

            if (!$isUserEvent && $userId !== null) {
                $chatId = $this->userTelegramRepository->findLinkedChatId($userId);
                if ($chatId !== null) {
                    $this->telegram->sendTo($chatId, $text, ['notification_type' => $message->type]);
                    return;
                }
            }

            $this->telegram->send($text, ['notification_type' => $message->type]);
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
