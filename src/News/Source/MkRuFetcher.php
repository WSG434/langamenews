<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use Symfony\Component\DomCrawler\Crawler;

class MkRuFetcher extends AbstractHtmlFetcher
{
    // Matches /section/YYYY/MM/DD/slug.html but not /video/ /photo/ /gallery/
    private const NEWS_URL_PATTERN = '~^https?://www\.mk\.ru/(?!video|photo|gallery|multimedia)[a-z]+/(\d{4})/(\d{2})/(\d{2})/[^/?#]+\.html~i';

    public function supports(NewsSource $source): bool
    {
        return $source->getCode() === 'mkru';
    }

    protected function parseItems(Crawler $crawler, NewsSource $source): iterable
    {
        $seen = [];
        $count = 0;

        $anchors = $crawler->filter('a[href]');

        for ($i = 0; $i < $anchors->count(); $i++) {
            $a = $anchors->eq($i);
            $href = $a->attr('href') ?? '';

            if (!preg_match(self::NEWS_URL_PATTERN, $href, $m)) {
                continue;
            }

            if (isset($seen[$href])) {
                continue;
            }
            $seen[$href] = true;

            // Raw text contains time prefix like "23:06\n\nTitle"
            $raw = trim($a->text());
            // Strip leading HH:MM if present
            $title = preg_replace('/^\d{2}:\d{2}\s+/u', '', $raw);
            $title = trim($title);

            if ($title === '') {
                continue;
            }

            // Build date from URL path: YYYY/MM/DD
            $publishedAt = null;
            try {
                $publishedAt = new \DateTimeImmutable("{$m[1]}-{$m[2]}-{$m[3]}");
            } catch (\Exception) {}

            yield new NewsItemDto(
                title: $title,
                sourceUid: $href,
                summary: null,
                content: null,
                publishedAt: $publishedAt,
                url: $href,
                imageUrl: null,
            );

            $count++;
        }

        $this->logger->info('mk.ru fetched', ['source' => $source->getCode(), 'items' => $count]);
    }
}
