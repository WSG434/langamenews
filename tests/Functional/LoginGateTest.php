<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginGateTest extends WebTestCase
{
    public function testUnverifiedUserCannotLogin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('unverified@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'unverified@example.com',
            '_password' => 'password123',
        ]);
        $client->submit($form);

        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/register/confirm/', $location);
    }
}
