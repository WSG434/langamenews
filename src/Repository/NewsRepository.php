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

    /**
     * @param string[] $excludedSources
     * @return array<array<string, mixed>>
     */
    public function searchFullText(string $query, int $limit = 20, array $excludedSources = []): array
    {
        $safeLimit = max(1, (int) $limit);
        $booleanQuery = implode(' ', array_map(
            fn (string $w) => '+' . $w . '*',
            array_filter(array_map('trim', preg_split('/\s+/', $query) ?: []))
        ));

        $excludeClause = '';
        $params = ['q' => $booleanQuery];
        $types = [];
        if ($excludedSources !== []) {
            $excludeClause = 'AND source NOT IN (:excluded)';
            $params['excluded'] = $excludedSources;
            $types['excluded'] = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        $sql = <<<SQL
            SELECT id, title, summary, url, image_url, published_at, source,
                   MATCH(title, summary, content) AGAINST(:q IN BOOLEAN MODE) AS score
            FROM news
            WHERE MATCH(title, summary, content) AGAINST(:q IN BOOLEAN MODE)
            $excludeClause
            ORDER BY score DESC
            LIMIT {$safeLimit}
        SQL;

        return $this->connection->fetchAllAssociative($sql, $params, $types);
    }

    /**
     * @param string[] $excludedSources
     * @return News[]
     */
    public function findLatest(int $limit = 10, int $offset = 0, array $excludedSources = []): array
    {
        if ($excludedSources === []) {
            return $this->findBy([], ['publishedAt' => 'DESC'], $limit, $offset);
        }

        return $this->createQueryBuilder('n')
            ->where('n.source NOT IN (:excluded)')
            ->setParameter('excluded', $excludedSources)
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
