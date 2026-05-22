<?php

namespace App\Tests\Functional\Notifications;

use App\Entity\ConfirmationCode;
use App\Entity\Notification;
use App\Entity\NewsSource;
use App\Entity\User;
use App\News\Dto\NewsItemDto;
use App\News\NewsImporter;
use App\News\Source\NewsSourceFetcherInterface;
use App\Notification\NotificationDispatcher;
use App\Notification\NotificationSenderInterface;
use App\Notification\NotificationMessage;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EventDispatchTest extends WebTestCase
{
    public function testUserRegisteredNotificationCreatedAfterConfirmation(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUnverifiedUser($em, 'notify_reg@example.com');
        $code = new ConfirmationCode($user, '123456');
        $em->persist($code);
        $em->flush();

        $client->request('POST', "/register/confirm/{$user->getId()}", ['code' => '123456']);

        $em->clear();
        $notifications = $em->getRepository(Notification::class)->findBy(['type' => 'user_registered']);
        $this->assertCount(1, $notifications);
        $this->assertSame('notify_reg@example.com', $notifications[0]->getPayload()['email']);
    }

    public function testLoginSuccessNotificationCreated(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('notify_login@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'notify_login@example.com',
            '_password' => 'password123',
        ]);
        $client->submit($form);

        $em->clear();
        $notifications = $em->getRepository(Notification::class)->findBy(['type' => 'user_logged_in']);
        $this->assertCount(1, $notifications);
        $this->assertSame('notify_login@example.com', $notifications[0]->getPayload()['email']);
    }

    public function testNewsImportedNotificationCreatedWhenNewItems(): void
    {
        $em = static::bootKernel()->getContainer()->get('doctrine.orm.entity_manager');

        $source = new NewsSource('test_notify_src', 'Test Source', 'http://example.com/rss');
        $source->setEnabled(true);
        $em->persist($source);
        $em->flush();

        $dto = new NewsItemDto(
            title: 'Test news ' . uniqid(),
            sourceUid: 'uid-notify-' . uniqid(),
            summary: 'Summary',
            content: '',
            publishedAt: new \DateTimeImmutable(),
            url: 'http://example.com/news/1',
            imageUrl: null,
        );

        $fetcher = new class($dto) implements NewsSourceFetcherInterface {
            public function __construct(private readonly NewsItemDto $dto) {}
            public function supports(NewsSource $s): bool { return true; }
            public function fetch(NewsSource $s): iterable { yield $this->dto; }
        };

        $notifRepo = $em->getRepository(Notification::class);
        $dispatcher = $this->makeDispatcher($notifRepo);

        $importer = new NewsImporter(
            [$fetcher],
            $em->getRepository(\App\Entity\News::class),
            $em,
            new \Psr\Log\NullLogger(),
            $dispatcher,
        );

        $importer->import($source);

        $notifications = $notifRepo->findBy(['type' => 'news_imported']);
        $this->assertCount(1, $notifications);
        $this->assertSame(1, $notifications[0]->getPayload()['count']);
    }

    public function testNewsImportedNotificationNotCreatedWhenNoNewItems(): void
    {
        $em = static::bootKernel()->getContainer()->get('doctrine.orm.entity_manager');

        $source = new NewsSource('test_notify_empty_' . uniqid(), 'Empty Source', 'http://example.com/rss-empty');
        $source->setEnabled(true);
        $em->persist($source);
        $em->flush();

        $fetcher = new class implements NewsSourceFetcherInterface {
            public function supports(NewsSource $s): bool { return true; }
            public function fetch(NewsSource $s): iterable { return []; }
        };

        $notifRepo = $em->getRepository(Notification::class);
        $countBefore = count($notifRepo->findBy(['type' => 'news_imported']));

        $importer = new NewsImporter(
            [$fetcher],
            $em->getRepository(\App\Entity\News::class),
            $em,
            new \Psr\Log\NullLogger(),
            $this->makeDispatcher($notifRepo),
        );

        $importer->import($source);

        $countAfter = count($em->getRepository(Notification::class)->findBy(['type' => 'news_imported']));
        $this->assertSame($countBefore, $countAfter);
    }

    private function makeDispatcher(NotificationRepository $repo): NotificationDispatcher
    {
        $sseSender = new class($repo) implements NotificationSenderInterface {
            public function __construct(private readonly NotificationRepository $r) {}
            public function send(NotificationMessage $m): void {
                $this->r->save(new Notification($m->type, $m->payload));
            }
        };
        return new NotificationDispatcher([$sseSender]);
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
