<?php

namespace App\Tests\Functional\Messenger;

use App\Entity\ConfirmationCode;
use App\Entity\User;
use App\Entity\UserTelegram;
use App\Message\SendConfirmationCodeMessage;
use App\MessageHandler\SendConfirmationCodeHandler;
use App\Repository\ConfirmationCodeRepository;
use App\Repository\UserTelegramRepository;
use App\Service\Telegram\TelegramSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SendConfirmationCodeHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ConfirmationCodeRepository $codeRepo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->codeRepo = static::getContainer()->get(ConfirmationCodeRepository::class);
    }

    private function createUser(): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail('handler_test_' . uniqid() . '@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function testHandlerSendsCodeAndMarksSent(): void
    {
        $user = $this->createUser();

        $tg = new UserTelegram($user);
        $tg->link(99999);
        $this->em->persist($tg);

        $code = new ConfirmationCode($user, '123456');
        $this->em->persist($code);
        $this->em->flush();

        $requestsMade = 0;
        $client = new MockHttpClient(function () use (&$requestsMade) {
            $requestsMade++;
            return new MockResponse('{}', ['http_code' => 200]);
        });

        $sender = new TelegramSender($client, 'token', '12345', new NullLogger());
        $telegramRepo = static::getContainer()->get(UserTelegramRepository::class);
        $handler = new SendConfirmationCodeHandler($this->codeRepo, $sender, $telegramRepo, $this->em, new NullLogger());
        $handler(new SendConfirmationCodeMessage($code->getId()));

        $this->em->refresh($code);
        $this->assertSame(ConfirmationCode::STATUS_SENT, $code->getStatus());
        $this->assertNotNull($code->getSentAt());
        $this->assertSame(1, $requestsMade);
    }

    public function testHandlerIsIdempotentForAlreadySentCode(): void
    {
        $user = $this->createUser();
        $code = new ConfirmationCode($user, '654321');
        $code->markSent();
        $this->em->persist($code);
        $this->em->flush();

        $requestsMade = 0;
        $client = new MockHttpClient(function () use (&$requestsMade) {
            $requestsMade++;
            return new MockResponse('{}', ['http_code' => 200]);
        });

        $sender = new TelegramSender($client, 'token', '12345', new NullLogger());
        $telegramRepo = static::getContainer()->get(UserTelegramRepository::class);
        $handler = new SendConfirmationCodeHandler($this->codeRepo, $sender, $telegramRepo, $this->em, new NullLogger());
        $handler(new SendConfirmationCodeMessage($code->getId()));

        $this->assertSame(0, $requestsMade);
    }
}
