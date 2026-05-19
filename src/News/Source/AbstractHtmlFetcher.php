<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractHtmlFetcher implements NewsSourceFetcherInterface
{
    private const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
    ) {}

    final public function fetch(NewsSource $source): iterable
    {
        try {
            $html = $this->httpClient->request('GET', $source->getUrl(), [
                'headers' => ['User-Agent' => self::USER_AGENT],
            ])->getContent();
        } catch (\Throwable $e) {
            $this->logger->error('HTML fetch failed', ['source' => $source->getCode(), 'error' => $e->getMessage()]);
            throw new \RuntimeException("Failed to fetch HTML from {$source->getUrl()}: {$e->getMessage()}", 0, $e);
        }

        yield from $this->parseItems(new Crawler($html), $source);
    }

    abstract protected function parseItems(Crawler $crawler, NewsSource $source): iterable;
}
