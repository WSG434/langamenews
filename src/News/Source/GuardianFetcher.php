<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GuardianFetcher extends AbstractJsonApiFetcher
{
    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        private readonly string $apiKey,
    ) {
        parent::__construct($httpClient, $logger);
    }

    public function supports(NewsSource $source): bool
    {
        return $source->getCode() === 'guardian';
    }

    public function fetch(NewsSource $source): iterable
    {
        try {
            $data = $this->getJson($source->getUrl(), [
                'query' => ['api-key' => $this->apiKey, 'show-fields' => 'bodyText,thumbnail', 'page-size' => 20],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Guardian fetch failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Guardian fetch failed: ' . $e->getMessage(), 0, $e);
        }

        $results = $data['response']['results'] ?? [];
        $count = 0;

        foreach ($results as $item) {
            $uid = $item['id'] ?? null;
            $title = trim($item['webTitle'] ?? '');
            if ($uid === null || $title === '') {
                continue;
            }

            $publishedAt = null;
            if (!empty($item['webPublicationDate'])) {
                try {
                    $publishedAt = new \DateTimeImmutable($item['webPublicationDate']);
                } catch (\Exception) {}
            }

            yield new NewsItemDto(
                title: $title,
                sourceUid: $uid,
                summary: null,
                content: $item['fields']['bodyText'] ?? null,
                publishedAt: $publishedAt,
                url: $item['webUrl'] ?? null,
                imageUrl: $item['fields']['thumbnail'] ?? null,
            );

            $count++;
        }

        $this->logger->info('Guardian fetched', ['items' => $count]);
    }
}
