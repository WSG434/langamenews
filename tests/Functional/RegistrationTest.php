<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationTest extends WebTestCase
{
    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);
        $client->submit($form);

        // Now redirects to confirm page, not login
        $this->assertResponseRedirects();
        $this->assertStringContainsString('/register/confirm/', $client->getResponse()->headers->get('Location'));

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'newuser@example.com']);

        $this->assertNotNull($user);
        $this->assertNotSame('password123', $user->getPassword(), 'Password must be hashed');
        $this->assertStringStartsWith('$', $user->getPassword());
        $this->assertFalse($user->isVerified());
    }

    public function testDuplicateEmailShowsError(): void
    {
        $client = static::createClient();

        // Register first time
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);
        $client->submit($form);

        // Now follow to confirm page
        $this->assertResponseRedirects();

        // Register again with same email
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.flash-error');
    }

    public function testWeakPasswordShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'weak@example.com',
            'registration_form[plainPassword][first]' => 'short',
            'registration_form[plainPassword][second]' => 'short',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'at least 8 characters');
    }
}
