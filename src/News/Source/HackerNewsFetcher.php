<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;

class HackerNewsFetcher extends AbstractJsonApiFetcher
{
    private const BASE_URL = 'https://hacker-news.firebaseio.com/v0';
    private const LIMIT = 30;

    public function supports(NewsSource $source): bool
    {
        return $source->getCode() === 'hackernews';
    }

    public function fetch(NewsSource $source): iterable
    {
        try {
            $ids = $this->getJson(self::BASE_URL . '/topstories.json');
        } catch (\Throwable $e) {
            $this->logger->error('HackerNews topstories fetch failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('HackerNews fetch failed: ' . $e->getMessage(), 0, $e);
        }

        $count = 0;
        foreach (array_slice($ids, 0, self::LIMIT) as $id) {
            try {
                $item = $this->getJson(self::BASE_URL . "/item/{$id}.json");
            } catch (\Throwable $e) {
                $this->logger->warning('HackerNews item fetch failed', ['id' => $id, 'error' => $e->getMessage()]);
                continue;
            }

            if (empty($item['title']) || ($item['type'] ?? '') !== 'story') {
                continue;
            }

            yield new NewsItemDto(
                title: $item['title'],
                sourceUid: (string) $item['id'],
                summary: null,
                content: null,
                publishedAt: isset($item['time']) ? new \DateTimeImmutable('@' . $item['time']) : null,
                url: $item['url'] ?? "https://news.ycombinator.com/item?id={$item['id']}",
                imageUrl: null,
            );

            $count++;
        }

        $this->logger->info('HackerNews fetched', ['items' => $count]);
    }
}
