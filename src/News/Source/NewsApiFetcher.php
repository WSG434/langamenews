<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NewsApiFetcher extends AbstractJsonApiFetcher
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
        return $source->getCode() === 'newsapi';
    }

    public function fetch(NewsSource $source): iterable
    {
        try {
            $data = $this->getJson($source->getUrl(), [
                'headers' => ['X-Api-Key' => $this->apiKey],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('NewsAPI fetch failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('NewsAPI fetch failed: ' . $e->getMessage(), 0, $e);
        }

        $count = 0;
        foreach ($data['articles'] ?? [] as $article) {
            $uid = $article['url'] ?? null;
            $title = trim($article['title'] ?? '');
            if ($uid === null || $title === '' || $title === '[Removed]') {
                continue;
            }

            $publishedAt = null;
            if (!empty($article['publishedAt'])) {
                try {
                    $publishedAt = new \DateTimeImmutable($article['publishedAt']);
                } catch (\Exception) {}
            }

            yield new NewsItemDto(
                title: $title,
                sourceUid: $uid,
                summary: $article['description'] ?? null,
                content: $article['content'] ?? null,
                publishedAt: $publishedAt,
                url: $article['url'] ?? null,
                imageUrl: $article['urlToImage'] ?? null,
            );

            $count++;
        }

        $this->logger->info('NewsAPI fetched', ['items' => $count]);
    }
}
