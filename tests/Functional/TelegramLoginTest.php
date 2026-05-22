<?php

namespace App\Tests\Functional;

use App\Entity\TelegramLoginToken;
use App\Entity\User;
use App\Entity\UserTelegram;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TelegramLoginTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
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
        $this->assertSelectorExists('input[name="code"]');
    }

    public function testInvalidCodeShowsError(): void
    {
        $client = static::createClient();
        $client->request('POST', '/login/telegram', ['code' => '000000']);
        $this->assertSelectorExists('.flash-error');
    }

    public function testValidCodeLogsIn(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUserWithTelegram('tg_verify@example.com', 99998);

        $token = new TelegramLoginToken(99998, '555444');
        $this->em()->persist($token);
        $this->em()->flush();

        $client->request('POST', '/login/telegram', ['code' => '555444']);

        $this->assertResponseRedirects('/news');
    }

    public function testExpiredCodeShowsError(): void
    {
        $client = static::createClient();
        $this->createVerifiedUserWithTelegram('tg_expired@example.com', 99997);

        $token = new TelegramLoginToken(99997, '111222', -1);
        $this->em()->persist($token);
        $this->em()->flush();

        $client->request('POST', '/login/telegram', ['code' => '111222']);

        $this->assertSelectorExists('.flash-error');
    }
}
