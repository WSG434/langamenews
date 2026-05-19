<?php

namespace App\Tests\Unit\News\Source;

use App\Entity\NewsSource;
use App\News\Source\GuardianFetcher;
use App\News\Source\HackerNewsFetcher;
use App\News\Source\NewsApiFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class JsonApiFetcherTest extends TestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__ . '/../../../fixtures/json/' . $name);
    }

    private function makeSource(string $code, string $type = NewsSource::TYPE_JSON_API): NewsSource
    {
        return new NewsSource($code, $code, 'https://example.com/api', $type);
    }

    // --- HackerNews ---

    public function testHackerNewsFetchesTopStories(): void
    {
        $topStories = $this->fixture('hackernews_topstories.json');
        $item = $this->fixture('hackernews_item.json');

        $client = new MockHttpClient([
            new MockResponse($topStories),
            new MockResponse($item),
            new MockResponse(str_replace('1001', '1002', str_replace('"story"', '"story"', $item))),
            new MockResponse(str_replace('1001', '1003', $item)),
        ]);

        $fetcher = new HackerNewsFetcher($client, new NullLogger());
        $items = iterator_to_array($fetcher->fetch($this->makeSource('hackernews')));

        $this->assertNotEmpty($items);
        $this->assertSame('Show HN: My new project', $items[0]->title);
        $this->assertSame('1001', $items[0]->sourceUid);
        $this->assertSame('https://example.com/project', $items[0]->url);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
    }

    public function testHackerNewsSupportsOnlyHackerNewsCode(): void
    {
        $fetcher = new HackerNewsFetcher(new MockHttpClient(), new NullLogger());
        $this->assertTrue($fetcher->supports($this->makeSource('hackernews')));
        $this->assertFalse($fetcher->supports($this->makeSource('other')));
    }

    public function testHackerNewsSkipsNonStoryItems(): void
    {
        $job = '{"id": 9999, "type": "job", "title": "Job posting"}';
        $client = new MockHttpClient([
            new MockResponse('[9999]'),
            new MockResponse($job),
        ]);

        $fetcher = new HackerNewsFetcher($client, new NullLogger());
        $items = iterator_to_array($fetcher->fetch($this->makeSource('hackernews')));
        $this->assertCount(0, $items);
    }

    // --- NewsAPI ---

    public function testNewsApiParsesArticles(): void
    {
        $client = new MockHttpClient(new MockResponse($this->fixture('newsapi_response.json')));
        $fetcher = new NewsApiFetcher($client, new NullLogger(), 'test-key');
        $items = iterator_to_array($fetcher->fetch($this->makeSource('newsapi')));

        // [Removed] articles are skipped
        $this->assertCount(1, $items);
        $this->assertSame('Test article one', $items[0]->title);
        $this->assertSame('Description of article one', $items[0]->summary);
        $this->assertSame('https://bbc.com/news/article-one', $items[0]->sourceUid);
        $this->assertSame('https://bbc.com/images/one.jpg', $items[0]->imageUrl);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
    }

    public function testNewsApiSupportsOnlyNewsApiCode(): void
    {
        $fetcher = new NewsApiFetcher(new MockHttpClient(), new NullLogger(), 'key');
        $this->assertTrue($fetcher->supports($this->makeSource('newsapi')));
        $this->assertFalse($fetcher->supports($this->makeSource('guardian')));
    }

    // --- Guardian ---

    public function testGuardianParsesResults(): void
    {
        $client = new MockHttpClient(new MockResponse($this->fixture('guardian_response.json')));
        $fetcher = new GuardianFetcher($client, new NullLogger(), 'test-key');
        $items = iterator_to_array($fetcher->fetch($this->makeSource('guardian')));

        $this->assertCount(1, $items);
        $this->assertSame('Guardian test article', $items[0]->title);
        $this->assertSame('world/2026/may/19/test-article', $items[0]->sourceUid);
        $this->assertSame('Article body text here', $items[0]->content);
        $this->assertSame('https://media.guim.co.uk/thumb.jpg', $items[0]->imageUrl);
        $this->assertInstanceOf(\DateTimeImmutable::class, $items[0]->publishedAt);
    }

    public function testGuardianSupportsOnlyGuardianCode(): void
    {
        $fetcher = new GuardianFetcher(new MockHttpClient(), new NullLogger(), 'key');
        $this->assertTrue($fetcher->supports($this->makeSource('guardian')));
        $this->assertFalse($fetcher->supports($this->makeSource('newsapi')));
    }
}
