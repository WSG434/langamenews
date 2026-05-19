<?php

namespace App\News\Source;

use App\Entity\NewsSource;

/**
 * Fallback fetcher for any RSS source without a dedicated implementation.
 * Lowest priority — registered last so specific fetchers take precedence.
 */
class GenericRssFetcher extends AbstractRssFetcher
{
    public function supports(NewsSource $source): bool
    {
        return $source->getType() === NewsSource::TYPE_RSS;
    }
}
