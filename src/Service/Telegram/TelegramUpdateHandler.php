<?php

namespace App\Service\Telegram;

use App\Repository\ConfirmationCodeRepository;
use App\Repository\UserTelegramRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly UserTelegramRepository $telegramRepository,
        private readonly ConfirmationCodeRepository $confirmationCodes,
        private readonly EntityManagerInterface $em,
        private readonly TelegramSender $sender,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(array $update): void
    {
        $text = $update['message']['text'] ?? '';
        $chatId = $update['message']['chat']['id'] ?? null;

        if ($chatId === null || !str_starts_with($text, '/start ')) {
            return;
        }

        $token = trim(substr($text, 7));
        $userTelegram = $this->telegramRepository->findByToken($token);

        if ($userTelegram === null) {
            $this->sender->sendTo($chatId, 'Ссылка недействительна. Зайдите в настройки профиля и получите новую.');
            return;
        }

        if (!$userTelegram->isLinked()) {
            $userTelegram->link($chatId);
            $this->em->flush();
        }

        $activeCode = $this->confirmationCodes->findActiveForUser($userTelegram->getUser());
        if ($activeCode !== null) {
            $this->sender->sendTo($chatId, "Ваш код подтверждения: {$activeCode->getCode()}");
            $activeCode->markSent();
            $this->em->flush();
        }
    }
}
