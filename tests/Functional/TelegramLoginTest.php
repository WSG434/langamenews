<?php

namespace App\Tests\Functional;

use App\Entity\ConfirmationCode;
use App\Entity\User;
use App\Entity\UserTelegram;
use App\Service\Telegram\TelegramSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TelegramLoginTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function mockTelegram(): void
    {
        $sender = new TelegramSender(
            new MockHttpClient(fn() => new MockResponse('{}', ['http_code' => 200])),
            'token',
            '0',
            new NullLogger(),
        );
        static::getContainer()->set(TelegramSender::class, $sender);
    }

    private function createVerifiedUserWithTelegram(string $email, int $chatId): User
    {
        $em = $this->em();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);
        $em->persist($user);

        $tg = new UserTelegram($user);
        $tg->link($chatId);
        $em->persist($tg);

        $em->flush();

        return $user;
    }

    public function testTelegramLoginPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login/telegram');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="email"]');
    }

    public function testUnknownEmailShowsError(): void
    {
        $client = static::createClient();
        $client->request('POST', '/login/telegram', ['email' => 'nobody@example.com']);
        $this->assertSelectorExists('.flash-error');
    }

    public function testUserWithoutTelegramShowsError(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('no_tg@example.com');
        $user->setPassword($hasher->hashPassword($user, 'pass'));
        $user->setIsVerified(true);
        $this->em()->persist($user);
        $this->em()->flush();

        $client->request('POST', '/login/telegram', ['email' => 'no_tg@example.com']);
        $this->assertSelectorExists('.flash-error');
    }

    public function testValidEmailWithTelegramRedirectsToVerify(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUserWithTelegram('tg_login@example.com', 99999);
        $this->mockTelegram();

        $client->request('POST', '/login/telegram', ['email' => 'tg_login@example.com']);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login/telegram/verify/', $client->getResponse()->headers->get('Location'));
    }

    public function testValidCodeLogsIn(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUserWithTelegram('tg_verify@example.com', 99998);

        $code = new ConfirmationCode($user, '555444');
        $this->em()->persist($code);
        $this->em()->flush();

        $client->request('POST', "/login/telegram/verify/{$user->getId()}", ['code' => '555444']);

        $this->assertResponseRedirects('/news');
    }

    public function testInvalidCodeShowsError(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUserWithTelegram('tg_badcode@example.com', 99997);

        $code = new ConfirmationCode($user, '111222');
        $this->em()->persist($code);
        $this->em()->flush();

        $client->request('POST', "/login/telegram/verify/{$user->getId()}", ['code' => '000000']);

        $this->assertSelectorExists('.flash-error');
    }
}
