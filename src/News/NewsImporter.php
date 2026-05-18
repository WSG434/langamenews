<?php

namespace App\News;

use App\Entity\News;
use App\Entity\NewsSource;
use App\News\Source\NewsSourceFetcherInterface;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NewsImporter
{
    /** @param iterable<NewsSourceFetcherInterface> $fetchers */
    public function __construct(
        private readonly iterable $fetchers,
        private readonly NewsRepository $newsRepository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function import(NewsSource $source): int
    {
        $fetcher = $this->findFetcher($source);
        if ($fetcher === null) {
            $this->logger->warning('No fetcher for source', ['source' => $source->getCode(), 'type' => $source->getType()]);
            return 0;
        }

        $this->logger->info('Import started', ['source' => $source->getCode()]);

        $newCount = 0;
        $skipped = 0;

        foreach ($fetcher->fetch($source) as $dto) {
            if ($this->newsRepository->existsBySourceUid($source->getCode(), $dto->sourceUid)) {
                $skipped++;
                continue;
            }

            $news = new News(
                title: $dto->title,
                source: $source->getCode(),
                sourceUid: $dto->sourceUid,
                summary: $dto->summary,
                content: $dto->content,
                publishedAt: $dto->publishedAt,
            );

            $this->em->persist($news);
            $newCount++;
        }

        $source->markFetched();
        $this->em->flush();

        $this->logger->info('Import finished', [
            'source' => $source->getCode(),
            'new' => $newCount,
            'skipped' => $skipped,
        ]);

        return $newCount;
    }

    private function findFetcher(NewsSource $source): ?NewsSourceFetcherInterface
    {
        foreach ($this->fetchers as $fetcher) {
            if ($fetcher->supports($source)) {
                return $fetcher;
            }
        }
        return null;
    }
}
