<?php

namespace App\Tests\Unit\News\Source;

use App\Entity\NewsSource;
use App\News\Source\MkRuFetcher;
use App\News\Source\ThreeDNewsFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class HtmlFetcherTest extends TestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/../../../fixtures/html/' . $name);
    }

    private function makeSource(string $code, string $url = 'https://example.com/'): NewsSource
    {
        return new NewsSource($code, $code, $url, NewsSource::TYPE_HTML);
    }

    // --- 3DNews ---

    public function testThreeDNewsParsesArticles(): void
    {
        $client = new MockHttpClient(new MockResponse($this->fixture('3dnews_sample.html')));
        $fetcher = new ThreeDNewsFetcher($client, new NullLogger());
        $items = iterator_to_array($fetcher->fetch($this->makeSource('3dnews', 'https://3dnews.ru/news/')));

        $this->assertCount(2, $items);
        $this->assertSame('Microsoft представила очень дорогие планшеты Surface Pro', $items[0]->title);
        $this->assertSame('1142001/microsoft-planshety', $items[0]->sourceUid);
        $this->assertStringStartsWith('https://3dnews.ru/', $items[0]->url);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
        $this->assertSame('2026-05-19', $items[0]->publishedAt->format('Y-m-d'));
    }

    public function testThreeDNewsSupportsOnlyItsCode(): void
    {
        $fetcher = new ThreeDNewsFetcher(new MockHttpClient(), new NullLogger());
        $this->assertTrue($fetcher->supports($this->makeSource('3dnews')));
        $this->assertFalse($fetcher->supports($this->makeSource('other')));
    }

    // --- MkRu ---

    public function testMkRuParsesNewsArticles(): void
    {
        $client = new MockHttpClient(new MockResponse($this->fixture('mkru_sample.html')));
        $fetcher = new MkRuFetcher($client, new NullLogger());
        $items = iterator_to_array($fetcher->fetch($this->makeSource('mkru', 'https://www.mk.ru/news/')));

        // /video/ link must be skipped
        $this->assertCount(2, $items);
        $this->assertSame('Трамп прокомментировал слова Си Цзиньпина', $items[0]->title);
        $this->assertSame('https://www.mk.ru/politics/2026/05/19/tramp-prokommentiroval-slova-si.html', $items[0]->sourceUid);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
        $this->assertSame('2026-05-19', $items[0]->publishedAt->format('Y-m-d'));
    }

    public function testMkRuSkipsVideoLinks(): void
    {
        $html = '<html><body>'
            . '<a href="https://www.mk.ru/video/2026/05/19/video.html">Видео</a>'
            . '<a href="https://www.mk.ru/politics/2026/05/19/news.html">Новость</a>'
            . '</body></html>';

        $client = new MockHttpClient(new MockResponse($html));
        $fetcher = new MkRuFetcher($client, new NullLogger());
        $items = iterator_to_array($fetcher->fetch($this->makeSource('mkru', 'https://www.mk.ru/news/')));

        $this->assertCount(1, $items);
        $this->assertStringContainsString('/politics/', $items[0]->url);
    }

    public function testMkRuSupportsOnlyItsCode(): void
    {
        $fetcher = new MkRuFetcher(new MockHttpClient(), new NullLogger());
        $this->assertTrue($fetcher->supports($this->makeSource('mkru')));
        $this->assertFalse($fetcher->supports($this->makeSource('other')));
    }

    public function testHtmlFetchFailureThrowsRuntimeException(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 500]));
        $fetcher = new ThreeDNewsFetcher($client, new NullLogger());
        $this->expectException(\RuntimeException::class);
        iterator_to_array($fetcher->fetch($this->makeSource('3dnews', 'https://3dnews.ru/news/')));
    }
}
