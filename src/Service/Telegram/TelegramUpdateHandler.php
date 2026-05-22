<?php

namespace App\Service\Telegram;

use App\Entity\TelegramLoginToken;
use App\Entity\User;
use App\Entity\UserTelegram;
use App\Service\CodeGenerator;
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
        $from = $update['message']['from'] ?? [];

        if ($chatId === null) {
            return;
        }

        if ($text === '/login') {
            $this->handleLogin($chatId, $from);
            return;
        }

        if (str_starts_with($text, '/start ')) {
            $param = trim(substr($text, 7));
            if ($param === 'login') {
                $this->handleLogin($chatId, $from);
            } else {
                $this->handleStart($chatId, $param);
            }
        }
    }


    private function handleLogin(int $chatId, array $from = []): void
    {
        $userTelegram = $this->telegramRepository->findByChatId($chatId);

        if ($userTelegram === null) {
            $user = new User();
            $user->setIsVerified(true);

            $handle = $from['username'] ?? $from['first_name'] ?? null;
            if ($handle !== null) {
                $user->setTelegramHandle($handle);
            }

            $this->em->persist($user);

            $userTelegram = new UserTelegram($user);
            $userTelegram->link($chatId);
            $this->em->persist($userTelegram);
            $this->em->flush();
        }

        $code = CodeGenerator::numeric();
        $token = new TelegramLoginToken($chatId, $code);
        $this->em->persist($token);
        $this->em->flush();

        $this->sender->sendTo($chatId, "Ваш код для входа: {$code}");
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
