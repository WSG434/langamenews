<?php

namespace App\News\Source;

use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;

interface NewsSourceFetcherInterface
{
    public function supports(NewsSource $source): bool;

    /** @return iterable<NewsItemDto> */
    public function fetch(NewsSource $source): iterable;
}
