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
        // Add wildcard suffix to each word for prefix matching in BOOLEAN MODE
        $booleanQuery = implode(' ', array_map(
            fn (string $w) => '+' . $w . '*',
            array_filter(array_map('trim', preg_split('/\s+/', $query) ?: []))
        ));

        $sql = <<<SQL
            SELECT id, title, summary, url, image_url, published_at, source,
                   MATCH(title, summary, content) AGAINST(:q IN BOOLEAN MODE) AS score
            FROM news
            WHERE MATCH(title, summary, content) AGAINST(:q IN BOOLEAN MODE)
            ORDER BY score DESC
            LIMIT $limit
        SQL;

        return $this->connection->fetchAllAssociative($sql, ['q' => $booleanQuery]);
    }

    /** @return News[] */
    public function findLatest(int $limit = 10, int $offset = 0): array
    {
        return $this->findBy([], ['publishedAt' => 'DESC'], $limit, $offset);
    }
}
