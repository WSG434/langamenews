<?php

namespace App\Tests\Functional;

use App\Entity\ConfirmationCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationFlowTest extends WebTestCase
{
    public function testRegistrationDispatchesMessageAndRedirectsToConfirm(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Зарегистрироваться')->form([
            'registration_form[email]' => 'flow_test@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/register/confirm/', $location);

        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');
        $this->assertCount(1, $transport->get());
    }

    public function testConfirmWithValidCodeVerifiesUser(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUnverifiedUser($em, 'confirm_test@example.com');
        $code = new ConfirmationCode($user, '999888');
        $em->persist($code);
        $em->flush();

        $userId = $user->getId();
        $codeId = $code->getId();

        $crawler = $client->request('GET', "/register/confirm/{$userId}");
        $form = $crawler->selectButton('Подтвердить')->form(['code' => '999888']);
        $client->submit($form);

        // After confirmation, user is auto-logged in and redirected to /news
        $this->assertResponseRedirects('/news');

        $em->clear();
        $user = $em->find(User::class, $userId);
        $code = $em->find(ConfirmationCode::class, $codeId);
        $this->assertTrue($user->isVerified());
        $this->assertSame(ConfirmationCode::STATUS_CONFIRMED, $code->getStatus());
    }

    public function testConfirmWithInvalidCodeIncrementsAttempts(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUnverifiedUser($em, 'invalid_code@example.com');
        $code = new ConfirmationCode($user, '111222');
        $em->persist($code);
        $em->flush();

        $codeId = $code->getId();

        $crawler = $client->request('GET', "/register/confirm/{$user->getId()}");
        $form = $crawler->selectButton('Подтвердить')->form(['code' => '000000']);
        $client->submit($form);

        $em->clear();
        $code = $em->find(ConfirmationCode::class, $codeId);
        $this->assertSame(1, $code->getAttempts());
        $this->assertFalse($code->getUser()->isVerified());
        $this->assertSelectorExists('.flash-error');
    }

    public function testConfirmWithExpiredCodeShowsError(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUnverifiedUser($em, 'expired_test@example.com');
        $code = new ConfirmationCode($user, '333444', -1);
        $em->persist($code);
        $em->flush();

        $crawler = $client->request('GET', "/register/confirm/{$user->getId()}");
        $form = $crawler->selectButton('Подтвердить')->form(['code' => '333444']);
        $client->submit($form);

        $this->assertSelectorExists('.flash-error');
    }

    private function createUnverifiedUser(EntityManagerInterface $em, string $email): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();
        return $user;
    }
}
