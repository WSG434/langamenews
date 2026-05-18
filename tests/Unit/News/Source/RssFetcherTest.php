<?php

namespace App\Tests\Unit\News\Source;

use App\Entity\NewsSource;
use App\News\Source\RssFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RssFetcherTest extends TestCase
{
    private function makeFetcher(string $body, int $statusCode = 200): RssFetcher
    {
        $client = new MockHttpClient(new MockResponse($body, ['http_code' => $statusCode]));
        return new RssFetcher($client, new NullLogger());
    }

    private function makeSource(): NewsSource
    {
        return new NewsSource('lenta', 'Lenta.ru', 'https://lenta.ru/rss/news');
    }

    public function testParsesValidRss(): void
    {
        $xml = file_get_contents(__DIR__ . '/../../../fixtures/rss/lenta_sample.xml');
        $fetcher = $this->makeFetcher($xml);

        $items = iterator_to_array($fetcher->fetch($this->makeSource()));

        $this->assertCount(3, $items);
        $this->assertSame('Первая новость', $items[0]->title);
        $this->assertSame('https://lenta.ru/news/2026/05/19/news1/', $items[0]->sourceUid);
        $this->assertSame('Описание первой новости', $items[0]->summary);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
    }

    public function testEmptyFeedReturnsNoItems(): void
    {
        $xml = file_get_contents(__DIR__ . '/../../../fixtures/rss/empty_feed.xml');
        $fetcher = $this->makeFetcher($xml);

        $items = iterator_to_array($fetcher->fetch($this->makeSource()));

        $this->assertCount(0, $items);
    }

    public function testInvalidXmlThrowsException(): void
    {
        $fetcher = $this->makeFetcher('this is not xml at all <<<');

        $this->expectException(\RuntimeException::class);
        iterator_to_array($fetcher->fetch($this->makeSource()));
    }

    public function testSupportsRssType(): void
    {
        $fetcher = $this->makeFetcher('');
        $this->assertTrue($fetcher->supports($this->makeSource()));
    }
}
