<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RssFetcher implements NewsSourceFetcherInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    public function supports(NewsSource $source): bool
    {
        return $source->getType() === NewsSource::TYPE_RSS;
    }

    public function fetch(NewsSource $source): iterable
    {
        try {
            $response = $this->httpClient->request('GET', $source->getUrl());
            $xml = $response->getContent();
        } catch (\Throwable $e) {
            $this->logger->error('RSS fetch failed', ['source' => $source->getCode(), 'error' => $e->getMessage()]);
            throw new \RuntimeException("Failed to fetch RSS from {$source->getUrl()}: {$e->getMessage()}", 0, $e);
        }

        try {
            libxml_use_internal_errors(true);
            $feed = new \SimpleXMLElement($xml);
            libxml_clear_errors();
        } catch (\Exception $e) {
            libxml_clear_errors();
            $this->logger->error('RSS parse failed', ['source' => $source->getCode(), 'error' => $e->getMessage()]);
            throw new \RuntimeException("Invalid RSS XML from {$source->getUrl()}: {$e->getMessage()}", 0, $e);
        }

        $items = $feed->channel->item ?? $feed->item ?? [];
        $count = 0;

        foreach ($items as $item) {
            $guid = (string) ($item->guid ?? $item->link ?? '');
            $title = (string) ($item->title ?? '');

            if ($guid === '' || $title === '') {
                $this->logger->warning('Skipping RSS item without guid/title', ['source' => $source->getCode()]);
                continue;
            }

            $publishedAt = null;
            $pubDate = (string) ($item->pubDate ?? '');
            if ($pubDate !== '') {
                $publishedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RSS, $pubDate) ?: null;
                if ($publishedAt === null) {
                    $publishedAt = (new \DateTimeImmutable($pubDate)) ?: null;
                }
            }

            yield new NewsItemDto(
                title: trim($title),
                sourceUid: $guid,
                summary: trim((string) ($item->description ?? '')) ?: null,
                content: null,
                publishedAt: $publishedAt,
            );

            $count++;
        }

        $this->logger->info('RSS fetched', ['source' => $source->getCode(), 'items' => $count]);
    }
}
