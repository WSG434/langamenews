<?php

namespace App\Tests\Functional;

use App\Entity\NewsSource;
use App\Entity\User;
use App\Entity\UserNewsSourcePreference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NewsSourcePreferenceTest extends WebTestCase
{
    private function createUser(EntityManagerInterface $em, UserPasswordHasherInterface $hasher, string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $user->setIsVerified(true);
        $em->persist($user);
        $em->flush();
        return $user;
    }

    private function createSource(EntityManagerInterface $em, string $code): NewsSource
    {
        $existing = $em->getRepository(NewsSource::class)->findOneBy(['code' => $code]);
        if ($existing !== null) {
            return $existing;
        }
        $source = new NewsSource($code, ucfirst($code), 'https://example.com/' . $code);
        $em->persist($source);
        $em->flush();
        return $source;
    }

    public function testSourcesPageRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news/sources');
        $this->assertResponseRedirects('/login');
    }

    public function testSourcesPageShowsSources(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = $this->createUser($em, $hasher, 'pref_test@test.com');
        $this->createSource($em, 'test_src_a');

        $client->loginUser($user);
        $client->request('GET', '/news/sources');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.toggle-btn');
    }

    public function testToggleDisablesSource(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = $this->createUser($em, $hasher, 'pref_toggle@test.com');
        $source = $this->createSource($em, 'test_src_b');

        $client->loginUser($user);
        $csrfToken = $this->fetchToggleCsrfToken($client);
        $client->request('POST', '/news/sources/' . $source->getId() . '/toggle', [], [], [
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['enabled']);

        // Preference row created
        $pref = $em->getRepository(UserNewsSourcePreference::class)
            ->findOneBy(['user' => $user, 'newsSource' => $source]);
        $this->assertNotNull($pref);
        $this->assertFalse($pref->isEnabled());
    }

    public function testToggleTwiceReEnablesSource(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = $this->createUser($em, $hasher, 'pref_retoggle@test.com');
        $source = $this->createSource($em, 'test_src_c');

        $client->loginUser($user);
        $csrfToken = $this->fetchToggleCsrfToken($client);
        $client->request('POST', '/news/sources/' . $source->getId() . '/toggle', [], [], [
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
        ]);
        $client->request('POST', '/news/sources/' . $source->getId() . '/toggle', [], [], [
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
        ]);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['enabled']);
    }

    private function fetchToggleCsrfToken(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): string
    {
        $crawler = $client->request('GET', '/news');
        return $crawler->filter('#source-chips')->attr('data-csrf') ?? '';
    }
}
