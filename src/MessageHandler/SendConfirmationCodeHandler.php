<?php

namespace App\MessageHandler;

use App\Entity\ConfirmationCode;
use App\Message\SendConfirmationCodeMessage;
use App\Repository\ConfirmationCodeRepository;
use App\Service\Telegram\TelegramSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendConfirmationCodeHandler
{
    public function __construct(
        private readonly ConfirmationCodeRepository $codes,
        private readonly TelegramSender $telegram,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(SendConfirmationCodeMessage $message): void
    {
        $code = $this->codes->find($message->codeId);

        if ($code === null) {
            $this->logger->error('ConfirmationCode not found', ['codeId' => $message->codeId]);
            return;
        }

        if ($code->getStatus() !== ConfirmationCode::STATUS_PENDING) {
            $this->logger->info('Skipping already processed code', [
                'codeId' => $message->codeId,
                'status' => $code->getStatus(),
            ]);
            return;
        }

        $context = ['codeId' => $message->codeId, 'userId' => $code->getUser()->getId()];

        $handle = $code->getUser()->getTelegramHandle();
        $mention = $handle ? "@{$handle} " : '';
        $text = "{$mention}Your confirmation code: {$code->getCode()}";

        $this->telegram->send($text, $context);

        $code->markSent();
        $this->em->flush();

        $this->logger->info('Confirmation code sent via Telegram', $context);
    }
}
