<?php

namespace App\Tests\Smoke;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminSmokeTest extends WebTestCase
{
    private function createUser(string $email, array $roles = []): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);
        $user->setRoles($roles);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testAdminRedirectsUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');
        $this->assertResponseRedirects();
    }

    public function testAdminForbiddenForRegularUser(): void
    {
        $client = static::createClient();
        $user = $this->createUser('regular@test.com');
        $client->loginUser($user);
        $client->request('GET', '/admin');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminAccessibleForAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $client->loginUser($admin);
        $client->request('GET', '/admin/user');
        $this->assertResponseIsSuccessful();
    }
}
