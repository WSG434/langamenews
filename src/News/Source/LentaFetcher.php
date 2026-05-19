<?php

namespace App\News\Source;

use App\Entity\NewsSource;

/**
 * Lenta.ru: image in <enclosure>, description is empty.
 * Default AbstractRssFetcher behaviour already handles enclosure — nothing to override.
 */
class LentaFetcher extends AbstractRssFetcher
{
    public function supports(NewsSource $source): bool
    {
        return $source->getCode() === 'lenta';
    }
}
