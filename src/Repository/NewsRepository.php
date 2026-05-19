<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Connection $connection)
    {
        parent::__construct($registry, News::class);
    }

    public function existsBySourceUid(string $source, string $sourceUid): bool
    {
        return $this->count(['source' => $source, 'sourceUid' => $sourceUid]) > 0;
    }

    /** @return array<array<string, mixed>> */
    public function searchFullText(string $query, int $limit = 20): array
    {
        $limit = max(1, (int) $limit);
        $sql = <<<SQL
            SELECT id, title, summary, published_at, source,
                   MATCH(title, summary, content) AGAINST(:q IN NATURAL LANGUAGE MODE) AS score
            FROM news
            WHERE MATCH(title, summary, content) AGAINST(:q IN NATURAL LANGUAGE MODE)
            ORDER BY score DESC
            LIMIT $limit
        SQL;

        return $this->connection->fetchAllAssociative($sql, ['q' => $query]);
    }

    /** @return News[] */
    public function findLatest(int $limit = 10): array
    {
        return $this->findBy([], ['publishedAt' => 'DESC'], $limit);
    }
}
