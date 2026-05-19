<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractRssFetcher implements NewsSourceFetcherInterface
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger,
    ) {}

    public function supports(NewsSource $source): bool
    {
        return $source->getType() === NewsSource::TYPE_RSS;
    }

    final public function fetch(NewsSource $source): iterable
    {
        try {
            $xml = $this->httpClient->request('GET', $source->getUrl())->getContent();
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
            $title = trim((string) ($item->title ?? ''));

            if ($guid === '' || $title === '') {
                $this->logger->warning('Skipping RSS item without guid/title', ['source' => $source->getCode()]);
                continue;
            }

            $pubDate = (string) ($item->pubDate ?? '');
            $publishedAt = $pubDate !== ''
                ? (\DateTimeImmutable::createFromFormat(\DateTimeInterface::RSS, $pubDate)
                    ?: (new \DateTimeImmutable($pubDate)) ?: null)
                : null;

            yield new NewsItemDto(
                title: $title,
                sourceUid: $guid,
                summary: $this->extractSummary($item),
                content: null,
                publishedAt: $publishedAt,
                url: trim((string) ($item->link ?? '')) ?: null,
                imageUrl: $this->extractImage($item),
            );

            $count++;
        }

        $this->logger->info('RSS fetched', ['source' => $source->getCode(), 'items' => $count]);
    }

    protected function extractSummary(\SimpleXMLElement $item): ?string
    {
        $text = strip_tags(trim((string) ($item->description ?? '')));
        return $text !== '' ? $text : null;
    }

    protected function extractImage(\SimpleXMLElement $item): ?string
    {
        $url = trim((string) ($item->enclosure['url'] ?? ''));
        $type = trim((string) ($item->enclosure['type'] ?? ''));
        if ($url !== '' && str_starts_with($type, 'image/')) {
            return $url;
        }
        return null;
    }
}
