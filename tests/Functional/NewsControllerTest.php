<?php

namespace App\Tests\Functional;

use App\Entity\News;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NewsControllerTest extends WebTestCase
{
    private function createVerifiedUser(string $email = 'news@example.com'): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createNewsItems(int $count): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        for ($i = 1; $i <= $count; $i++) {
            $news = new News(
                "Test News Title $i",
                'test-source',
                "uid-$i",
                "Summary for news item $i",
                null,
                new \DateTimeImmutable("-$i hours"),
            );
            $em->persist($news);
        }

        $em->flush();
    }

    public function testUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news');
        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedUserSeesNewsPage(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser();
        $this->createNewsItems(10);

        $client->loginUser($user);
        $client->request('GET', '/news');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.news-list');
    }

    public function testSearchReturnsJson(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser();
        $this->createNewsItems(3);

        $client->loginUser($user);
        $client->request('GET', '/news/search?q=Test');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testSearchEmptyQueryReturnsLatestTen(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser();
        $this->createNewsItems(15);

        $client->loginUser($user);
        $client->request('GET', '/news/search?q=');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(10, $data);
    }

    public function testSearchReturnsMatchingResults(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        for ($i = 1; $i <= 5; $i++) {
            $em->persist(new News("Symfony release $i", 'src', "uid-sym-$i", 'Great PHP framework news'));
        }
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/news/search?q=Symfony');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        if (!empty($data)) {
            $this->assertArrayHasKey('title', $data[0]);
            $this->assertStringNotContainsString('<mark>', $data[0]['title']);
        }
    }

    public function testHomeRedirectsToNewsWhenLoggedIn(): void
    {
        $client = static::createClient();
        $user = $this->createVerifiedUser();

        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseRedirects('/news');
    }
}
