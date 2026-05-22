<?php

namespace App\Tests\Unit\Service\Telegram;

use App\Entity\User;
use App\Entity\UserTelegram;
use App\Repository\ConfirmationCodeRepository;
use App\Repository\TelegramLoginTokenRepository;
use App\Repository\UserTelegramRepository;
use App\Service\Telegram\TelegramSender;
use App\Service\Telegram\TelegramUpdateHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TelegramUpdateHandlerTest extends TestCase
{
    private function makeHandler(
        UserTelegramRepository $telegramRepo,
        EntityManagerInterface $em,
        TelegramSender $sender,
    ): TelegramUpdateHandler {
        return new TelegramUpdateHandler(
            $telegramRepo,
            $this->createStub(ConfirmationCodeRepository::class),
            $this->createStub(TelegramLoginTokenRepository::class),
            $em,
            $sender,
            new NullLogger(),
        );
    }

    public function testHandleLoginAutoRegistersNewUser(): void
    {
        $telegramRepo = $this->createMock(UserTelegramRepository::class);
        $telegramRepo->method('findByChatId')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(3))->method('persist');
        $em->expects($this->exactly(2))->method('flush');

        $sender = $this->createMock(TelegramSender::class);
        $sender->expects($this->once())->method('sendTo')
            ->with(12345, $this->matchesRegularExpression('/\d{6}/'));

        $handler = $this->makeHandler($telegramRepo, $em, $sender);
        $handler->handle(['message' => ['chat' => ['id' => 12345], 'text' => '/start login']]);
    }

    public function testHandleLoginExistingUserSendsCode(): void
    {
        $user = new User();
        $tg = new UserTelegram($user);
        $tg->link(12345);

        $telegramRepo = $this->createMock(UserTelegramRepository::class);
        $telegramRepo->method('findByChatId')->willReturn($tg);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $sender = $this->createMock(TelegramSender::class);
        $sender->expects($this->once())->method('sendTo')
            ->with(12345, $this->matchesRegularExpression('/\d{6}/'));

        $handler = $this->makeHandler($telegramRepo, $em, $sender);
        $handler->handle(['message' => ['chat' => ['id' => 12345], 'text' => '/login']]);
    }

    public function testHandleIgnoresUpdateWithoutChatId(): void
    {
        $telegramRepo = $this->createMock(UserTelegramRepository::class);
        $telegramRepo->expects($this->never())->method('findByChatId');

        $em = $this->createMock(EntityManagerInterface::class);
        $sender = $this->createMock(TelegramSender::class);
        $sender->expects($this->never())->method('sendTo');

        $handler = $this->makeHandler($telegramRepo, $em, $sender);
        $handler->handle(['message' => ['text' => '/login']]);
    }
}
