<?php

namespace App\Tests\Functional\Notifications;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StreamControllerTest extends WebTestCase
{
    public function testUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/stream/notifications');
        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedReturnsEventStreamHeader(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->loginAs($client, $em, 'stream_auth@example.com');

        $client->request('GET', '/stream/notifications');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('text/event-stream', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertSame('no', $client->getResponse()->headers->get('X-Accel-Buffering'));
    }

    public function testFindAfterReturnsOnlyNotificationsAfterLastId(): void
    {
        $em = static::bootKernel()->getContainer()->get('doctrine.orm.entity_manager');

        $n1 = new Notification('user_logged_in', ['email' => 'a@a.com']);
        $n2 = new Notification('user_logged_in', ['email' => 'b@b.com']);
        $em->persist($n1);
        $em->persist($n2);
        $em->flush();

        /** @var NotificationRepository $repo */
        $repo = $em->getRepository(Notification::class);

        $results = $repo->findAfter($n1->getId());

        $this->assertCount(1, $results);
        $this->assertSame('b@b.com', $results[0]->getPayload()['email']);
    }

    private function loginAs(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, EntityManagerInterface $em, string $email): void
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);
    }
}
