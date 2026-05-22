<?php

namespace App\Service\Telegram;

use App\Entity\TelegramLoginToken;
use App\Repository\ConfirmationCodeRepository;
use App\Repository\TelegramLoginTokenRepository;
use App\Repository\UserTelegramRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TelegramUpdateHandler
{
    public function __construct(
        private readonly UserTelegramRepository $telegramRepository,
        private readonly ConfirmationCodeRepository $confirmationCodes,
        private readonly TelegramLoginTokenRepository $loginTokens,
        private readonly EntityManagerInterface $em,
        private readonly TelegramSender $sender,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(array $update): void
    {
        $text = $update['message']['text'] ?? '';
        $chatId = $update['message']['chat']['id'] ?? null;

        if ($chatId === null) {
            return;
        }

        if ($text === '/login') {
            $this->handleLogin($chatId);
            return;
        }

        if (str_starts_with($text, '/start ')) {
            $param = trim(substr($text, 7));
            if ($param === 'login') {
                $this->handleLogin($chatId);
            } else {
                $this->handleStart($chatId, $param);
            }
        }
    }

    private function handleLogin(int $chatId): void
    {
        $userTelegram = $this->telegramRepository->findByChatId($chatId);

        if ($userTelegram === null) {
            $this->sender->sendTo($chatId, 'Ваш Telegram не привязан ни к одному аккаунту.');
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = new TelegramLoginToken($chatId, $code);
        $this->em->persist($token);
        $this->em->flush();

        $this->sender->sendTo($chatId, "Ваш код для входа: {$code}\n\nВведите его на странице входа через Telegram. Код действителен 10 минут.");
    }

    private function handleStart(int $chatId, string $token): void
    {
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
