<?php

namespace App\Controller;

use App\Service\Telegram\TelegramUpdateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TelegramWebhookController extends AbstractController
{
    public function __construct(private readonly string $webhookSecret) {}

    #[Route('/telegram/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function __invoke(
        Request $request,
        TelegramUpdateHandler $handler,
        LoggerInterface $logger,
    ): JsonResponse {
        if ($this->webhookSecret !== '' && !hash_equals(
            $this->webhookSecret,
            $request->headers->get('X-Telegram-Bot-Api-Secret-Token', '')
        )) {
            return new JsonResponse(['ok' => false], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $handler->handle($data ?? []);
        } catch (\Throwable $e) {
            $logger->error('Telegram webhook error', ['error' => $e->getMessage()]);
        }

        return new JsonResponse(['ok' => true]);
    }
}
