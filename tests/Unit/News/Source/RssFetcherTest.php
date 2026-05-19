<?php

namespace App\Tests\Unit\News\Source;

use App\Entity\NewsSource;
use App\News\Source\GenericRssFetcher;
use App\News\Source\HabrFetcher;
use App\News\Source\LentaFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RssFetcherTest extends TestCase
{
    private function makeFetcher(string $class, string $body): object
    {
        $client = new MockHttpClient(new MockResponse($body));
        return new $class($client, new NullLogger());
    }

    private function makeSource(string $code = 'lenta'): NewsSource
    {
        return new NewsSource($code, ucfirst($code), 'https://example.com/rss');
    }

    private function loadFixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/../../../fixtures/rss/' . $name);
    }

    public function testParsesValidRss(): void
    {
        $fetcher = $this->makeFetcher(GenericRssFetcher::class, $this->loadFixture('lenta_sample.xml'));
        $items = iterator_to_array($fetcher->fetch($this->makeSource()));

        $this->assertCount(3, $items);
        $this->assertSame('Первая новость', $items[0]->title);
        $this->assertSame('https://lenta.ru/news/2026/05/19/news1/', $items[0]->sourceUid);
        $this->assertSame('Описание первой новости', $items[0]->summary);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
    }

    public function testEmptyFeedReturnsNoItems(): void
    {
        $fetcher = $this->makeFetcher(GenericRssFetcher::class, $this->loadFixture('empty_feed.xml'));
        $items = iterator_to_array($fetcher->fetch($this->makeSource()));
        $this->assertCount(0, $items);
    }

    public function testInvalidXmlThrowsException(): void
    {
        $fetcher = $this->makeFetcher(GenericRssFetcher::class, 'not xml <<<');
        $this->expectException(\RuntimeException::class);
        iterator_to_array($fetcher->fetch($this->makeSource()));
    }

    public function testGenericFetcherSupportsAnyRssSource(): void
    {
        $fetcher = $this->makeFetcher(GenericRssFetcher::class, '');
        $this->assertTrue($fetcher->supports($this->makeSource('anything')));
    }

    public function testLentaFetcherSupportsOnlyLenta(): void
    {
        $fetcher = $this->makeFetcher(LentaFetcher::class, '');
        $this->assertTrue($fetcher->supports($this->makeSource('lenta')));
        $this->assertFalse($fetcher->supports($this->makeSource('habr')));
    }

    public function testHabrFetcherSupportsOnlyHabr(): void
    {
        $fetcher = $this->makeFetcher(HabrFetcher::class, '');
        $this->assertTrue($fetcher->supports($this->makeSource('habr')));
        $this->assertFalse($fetcher->supports($this->makeSource('lenta')));
    }

    public function testHabrFetcherExtractsImageFromDescription(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <item>
              <title>Habr news</title>
              <link>https://habr.com/article/1</link>
              <guid>https://habr.com/article/1</guid>
              <description><![CDATA[<img src="https://habrastorage.org/img.png" /><p>Text here</p>]]></description>
              <pubDate>Mon, 19 May 2026 10:00:00 +0000</pubDate>
            </item>
          </channel>
        </rss>
        XML;

        $fetcher = $this->makeFetcher(HabrFetcher::class, $xml);
        $items = iterator_to_array($fetcher->fetch($this->makeSource('habr')));

        $this->assertCount(1, $items);
        $this->assertSame('https://habrastorage.org/img.png', $items[0]->imageUrl);
        $this->assertSame('Text here', $items[0]->summary);
    }

    public function testLentaFetcherExtractsImageFromEnclosure(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0">
          <channel>
            <item>
              <title>Lenta news</title>
              <link>https://lenta.ru/news/1</link>
              <guid>https://lenta.ru/news/1</guid>
              <description></description>
              <enclosure url="https://icdn.lenta.ru/photo.jpg" type="image/jpeg" length="12345"/>
              <pubDate>Mon, 19 May 2026 10:00:00 +0000</pubDate>
            </item>
          </channel>
        </rss>
        XML;

        $fetcher = $this->makeFetcher(LentaFetcher::class, $xml);
        $items = iterator_to_array($fetcher->fetch($this->makeSource('lenta')));

        $this->assertCount(1, $items);
        $this->assertSame('https://icdn.lenta.ru/photo.jpg', $items[0]->imageUrl);
        $this->assertNull($items[0]->summary);
    }
}
