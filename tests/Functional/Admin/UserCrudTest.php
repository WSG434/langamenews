<?php

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudTest extends WebTestCase
{
    private function createUser(string $email, array $roles = [], bool $verified = true): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified($verified);
        $user->setRoles($roles);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function adminClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $admin = $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $client->loginUser($admin);
        return $client;
    }

    public function testUserCrudIndexReturns200(): void
    {
        $client = $this->adminClient();
        $client->request('GET', '/admin/user');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
    }

    public function testConfirmationCodeCrudIndexReturns200(): void
    {
        $client = $this->adminClient();
        $client->request('GET', '/admin/confirmation-code');
        $this->assertResponseIsSuccessful();
    }

    public function testNewsCrudIndexReturns200(): void
    {
        $client = $this->adminClient();
        $client->request('GET', '/admin/news');
        $this->assertResponseIsSuccessful();
    }

    public function testUserListShowsVerifiedAndUnverified(): void
    {
        $client = $this->adminClient();
        $this->createUser('verified@test.com', [], true);
        $this->createUser('unverified@test.com', [], false);

        $client->request('GET', '/admin/user');
        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('verified@test.com', $content);
        $this->assertStringContainsString('unverified@test.com', $content);
    }
}
