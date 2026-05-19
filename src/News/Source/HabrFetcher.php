<?php

namespace App\News\Source;

use App\Entity\NewsSource;

/**
 * Habr: image is the first <img> inside <description> HTML; summary is the stripped text.
 */
class HabrFetcher extends AbstractRssFetcher
{
    public function supports(NewsSource $source): bool
    {
        return $source->getCode() === 'habr';
    }

    protected function extractImage(\SimpleXMLElement $item): ?string
    {
        $description = (string) ($item->description ?? '');
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $description, $m)) {
            return $m[1];
        }
        return null;
    }
}
