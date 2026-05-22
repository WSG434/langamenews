<?php

namespace App\Command;

use App\Service\Telegram\TelegramUpdateHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'telegram:poll', description: 'Poll Telegram for updates (use instead of webhook)')]
class TelegramPollingCommand extends Command
{
    public function __construct(
        private readonly TelegramUpdateHandler $handler,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $botToken,
        private readonly string $telegramMode,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->telegramMode === 'webhook') {
            $output->writeln('<error>TELEGRAM_MODE=webhook, polling is disabled.</error>');
            return Command::FAILURE;
        }

        $output->writeln('Polling Telegram... (Ctrl+C to stop)');

        $offset = 0;

        while (true) {
            try {
                $response = $this->httpClient->request('GET', "https://api.telegram.org/bot{$this->botToken}/getUpdates", [
                    'query' => ['offset' => $offset, 'timeout' => 30],
                    'timeout' => 35,
                ]);

                $data = $response->toArray(false);

                if (!($data['ok'] ?? false)) {
                    $this->logger->error('Telegram getUpdates failed', ['response' => $data]);
                    sleep(5);
                    continue;
                }

                foreach ($data['result'] as $update) {
                    try {
                        $this->handler->handle($update);
                    } catch (\Throwable $e) {
                        $this->logger->error('Telegram update handling error', ['error' => $e->getMessage()]);
                    }
                    $offset = $update['update_id'] + 1;
                }
            } catch (\Throwable $e) {
                $this->logger->error('Telegram polling error', ['error' => $e->getMessage()]);
                sleep(5);
            }
        }
    }
}
