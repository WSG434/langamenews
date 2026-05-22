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
    private const FT_MIN_TOKEN = 3;

    public function searchFullText(string $query, int $limit = 20, array $excludedSources = []): array
    {
        $safeLimit = max(1, (int) $limit);

        // Strip FULLTEXT boolean special chars to avoid syntax errors
        $sanitized = preg_replace('/[+\-><()~*"@!]/', ' ', $query) ?? '';
        $words = array_values(array_filter(
            array_map('trim', preg_split('/\s+/', $sanitized) ?: []),
            fn (string $w) => $w !== ''
        ));

        if ($words === []) {
            return [];
        }

        $ftWords    = array_filter($words, fn (string $w) => mb_strlen($w) >= self::FT_MIN_TOKEN);
        $shortWords = array_filter($words, fn (string $w) => mb_strlen($w) < self::FT_MIN_TOKEN);

        $params = [];
        $types  = [];

        $excludeClause = '';
        if ($excludedSources !== []) {
            $excludeClause = 'AND source NOT IN (:excluded)';
            $params['excluded'] = $excludedSources;
            $types['excluded'] = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        // Short words that can't be FULLTEXT-indexed are searched via LIKE
        $likeClause = '';
        foreach (array_values($shortWords) as $i => $w) {
            $key = "like_{$i}";
            $params[$key] = '%' . $w . '%';
            $likeClause .= " AND (title LIKE :{$key} OR summary LIKE :{$key})";
        }

        if ($ftWords !== []) {
            $booleanQuery = implode(' ', array_map(fn ($w) => '+' . $w . '*', $ftWords));
            $params['q']  = $booleanQuery;

            $sql = <<<SQL
                SELECT id, title, summary, url, image_url, published_at, source,
                       MATCH(title, summary, content) AGAINST(:q IN BOOLEAN MODE) AS score
                FROM news
                WHERE MATCH(title, summary, content) AGAINST(:q IN BOOLEAN MODE)
                $likeClause
                $excludeClause
                ORDER BY score DESC
                LIMIT {$safeLimit}
            SQL;
        } else {
            // All words are too short for FULLTEXT — pure LIKE search
            $likeParts = [];
            foreach (array_values($words) as $i => $w) {
                $key = "like_{$i}";
                $params[$key] = '%' . $w . '%';
                $likeParts[] = "(title LIKE :{$key} OR summary LIKE :{$key})";
            }
            $whereClause = implode(' AND ', $likeParts);

            $sql = <<<SQL
                SELECT id, title, summary, url, image_url, published_at, source, 0 AS score
                FROM news
                WHERE {$whereClause}
                $excludeClause
                ORDER BY published_at DESC
                LIMIT {$safeLimit}
            SQL;
        }

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
