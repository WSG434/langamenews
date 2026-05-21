<?php

namespace App\Controller;

use App\Entity\UserTelegram;
use App\Repository\UserTelegramRepository;
use App\Service\Telegram\TelegramSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TelegramWebhookController extends AbstractController
{
    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UserTelegramRepository $telegramRepository,
        EntityManagerInterface $em,
        TelegramSender $sender,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $text = $data['message']['text'] ?? '';
        $chatId = $data['message']['chat']['id'] ?? null;

        if ($chatId === null || !str_starts_with($text, '/start ')) {
            return new JsonResponse(['ok' => true]);
        }

        $token = trim(substr($text, 7));
        $userTelegram = $telegramRepository->findByToken($token);

        if ($userTelegram === null) {
            $sender->sendTo($chatId, 'Ссылка недействительна. Зайдите в настройки профиля и получите новую.');
            return new JsonResponse(['ok' => true]);
        }

        if ($userTelegram->isLinked()) {
            $sender->sendTo($chatId, 'Telegram уже привязан к вашему аккаунту.');
            return new JsonResponse(['ok' => true]);
        }

        $userTelegram->link($chatId);
        $em->flush();

        $sender->sendTo($chatId, 'Telegram успешно привязан! Теперь вы будете получать уведомления здесь.');

        return new JsonResponse(['ok' => true]);
    }
}
