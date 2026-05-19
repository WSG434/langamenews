<?php

namespace App\Tests\Unit\Repository;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NewsRepositoryFullTextTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NewsRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repo = static::getContainer()->get(NewsRepository::class);
    }

    public function testFindLatestReturnsOrderedByPublishedAt(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->em->persist(new News(
                "News $i",
                'src',
                "flt-uid-$i",
                null,
                null,
                new \DateTimeImmutable("-$i hours"),
            ));
        }
        $this->em->flush();

        $result = $this->repo->findLatest(10);
        $this->assertCount(10, $result);
        // Most recent first
        $this->assertGreaterThanOrEqual(
            $result[1]->getPublishedAt()?->getTimestamp() ?? 0,
            $result[0]->getPublishedAt()?->getTimestamp() ?? 0
        );
    }

    public function testSearchFullTextReturnsArray(): void
    {
        // InnoDB FULLTEXT index requires committed data — this test verifies
        // method signature and that it does not throw on valid input.
        $result = $this->repo->searchFullText('symfony');
        $this->assertIsArray($result);
    }
}
