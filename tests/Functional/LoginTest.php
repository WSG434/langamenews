<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    private function createTestUser(string $email, string $password): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testValidCredentialsRedirectsHome(): void
    {
        $client = static::createClient();
        $this->createTestUser('login@example.com', 'password123');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'login@example.com',
            '_password' => 'password123',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testInvalidPasswordShowsError(): void
    {
        $client = static::createClient();
        $this->createTestUser('badpass@example.com', 'password123');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'badpass@example.com',
            '_password' => 'wrongpassword',
        ]);
        $client->submit($form);

        $client->followRedirect();
        $this->assertSelectorExists('.flash-error');
    }

    public function testLogoutClearsSession(): void
    {
        $client = static::createClient();
        $user = $this->createTestUser('logout@example.com', 'password123');

        $client->loginUser($user);
        $client->request('GET', '/logout');

        $this->assertResponseRedirects();
        $client->followRedirect();

        // After logout should not show user identifier
        $crawler = $client->request('GET', '/');
        $this->assertStringNotContainsString('logout@example.com', $crawler->filter('nav')->text());
    }
}
