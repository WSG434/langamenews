<?php

namespace App\Tests\Functional\Command;

use App\Command\NewsImportCommand;
use App\Entity\News;
use App\Entity\NewsSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NewsImportCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createSource(string $code, bool $enabled = true): NewsSource
    {
        $source = new NewsSource($code, ucfirst($code), "https://{$code}.example.com/rss");
        $source->setEnabled($enabled);
        $this->em->persist($source);
        $this->em->flush();
        return $source;
    }

    private function runCommand(array $args = []): CommandTester
    {
        $command = static::getContainer()->get(NewsImportCommand::class);
        $tester = new CommandTester($command);
        $tester->execute($args);
        return $tester;
    }

    public function testImportsAllEnabledSources(): void
    {
        $xml = file_get_contents(__DIR__ . '/../../fixtures/rss/lenta_sample.xml');

        $mockClient = new MockHttpClient([
            new MockResponse($xml),
            new MockResponse($xml),
        ]);
        static::getContainer()->set('http_client', $mockClient);

        $this->createSource('source-a');
        $this->createSource('source-b');

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Total new:', $tester->getDisplay());

        $count = $this->em->getRepository(News::class)->count([]);
        $this->assertGreaterThan(0, $count);
    }

    public function testImportsSingleSource(): void
    {
        $xml = file_get_contents(__DIR__ . '/../../fixtures/rss/lenta_sample.xml');
        $mockClient = new MockHttpClient(new MockResponse($xml));
        static::getContainer()->set('http_client', $mockClient);

        $this->createSource('only-one');

        $tester = $this->runCommand(['--source' => 'only-one']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('[only-one]', $tester->getDisplay());
    }

    public function testUnknownSourceReturnsFailure(): void
    {
        $tester = $this->runCommand(['--source' => 'nonexistent']);
        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testIdempotentImport(): void
    {
        $xml = file_get_contents(__DIR__ . '/../../fixtures/rss/lenta_sample.xml');
        $mockClient = new MockHttpClient([
            new MockResponse($xml),
            new MockResponse($xml),
        ]);
        static::getContainer()->set('http_client', $mockClient);

        $this->createSource('idempotent-src');

        $this->runCommand(['--source' => 'idempotent-src']);
        $countAfterFirst = $this->em->getRepository(News::class)->count([]);

        $this->runCommand(['--source' => 'idempotent-src']);
        $countAfterSecond = $this->em->getRepository(News::class)->count([]);

        $this->assertSame($countAfterFirst, $countAfterSecond);
    }
}
