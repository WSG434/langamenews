<?php

namespace App\Controller;

use App\Service\Telegram\TelegramUpdateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TelegramWebhookController extends AbstractController
{
    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function __invoke(
        Request $request,
        TelegramUpdateHandler $handler,
        LoggerInterface $logger,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $handler->handle($data ?? []);
        } catch (\Throwable $e) {
            $logger->error('Telegram webhook error', ['error' => $e->getMessage()]);
        }

        return new JsonResponse(['ok' => true]);
    }
}
