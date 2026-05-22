<?php

namespace App\Service\Telegram;

use App\Entity\UserTelegram;

class TelegramLinkFactory
{
    public function __construct(private readonly string $botName) {}

    public function linkUrl(UserTelegram $userTelegram): string
    {
        return "https://t.me/{$this->botName}?start={$userTelegram->getLinkToken()}";
    }

    public function loginUrl(): string
    {
        return "https://t.me/{$this->botName}?start=login";
    }
}
