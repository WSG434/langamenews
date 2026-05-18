<?php

namespace App\Service\Telegram;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramSender
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $botToken,
        private readonly string $chatId,
        private readonly LoggerInterface $logger,
    ) {}

    public function send(string $text, array $context = []): void
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $response = $this->httpClient->request('POST', $url, [
            'json' => ['chat_id' => $this->chatId, 'text' => $text],
        ]);

        $status = $response->getStatusCode();

        if ($status >= 500) {
            $this->logger->error('Telegram API server error', array_merge($context, ['status' => $status]));
            throw new RecoverableMessageHandlingException("Telegram API error: {$status}");
        }

        if ($status >= 400) {
            $this->logger->error('Telegram API client error', array_merge($context, ['status' => $status]));
            throw new UnrecoverableMessageHandlingException("Telegram API error: {$status}");
        }

        $this->logger->info('Telegram message sent', array_merge($context, ['status' => $status]));
    }
}
