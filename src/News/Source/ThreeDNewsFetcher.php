<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use Symfony\Component\DomCrawler\Crawler;

class ThreeDNewsFetcher extends AbstractHtmlFetcher
{
    public function supports(NewsSource $source): bool
    {
        return $source->getCode() === '3dnews';
    }

    protected function parseItems(Crawler $crawler, NewsSource $source): iterable
    {
        $nodes = $crawler->filter('[class*="article-entry"]');
        $count = 0;

        for ($i = 0; $i < $nodes->count(); $i++) {
            $node = $nodes->eq($i);
            $links = $node->filter('a[href]');

            if ($links->count() < 2) {
                continue;
            }

            $href = $links->first()->attr('href') ?? '';
            // Last link carries the title text
            $title = trim($links->eq($links->count() - 1)->text());

            if ($href === '' || $title === '') {
                continue;
            }

            $sourceUid = ltrim($href, '/');
            $url = 'https://3dnews.ru' . ($href[0] === '/' ? $href : '/' . $href);

            $publishedAt = null;
            $text = trim($node->text());
            if (preg_match('/^(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2})/', $text, $m)) {
                $publishedAt = \DateTimeImmutable::createFromFormat('d.m.Y H:i', $m[1]) ?: null;
            }

            yield new NewsItemDto(
                title: $title,
                sourceUid: $sourceUid,
                summary: null,
                content: null,
                publishedAt: $publishedAt,
                url: $url,
                imageUrl: null,
            );

            $count++;
        }

        $this->logger->info('3dnews fetched', ['source' => $source->getCode(), 'items' => $count]);
    }
}
