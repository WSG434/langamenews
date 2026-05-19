<?php

namespace App\Tests\Unit\News;

use App\Entity\News;
use App\Entity\NewsSource;
use App\News\Dto\NewsItemDto;
use App\News\NewsImporter;
use App\News\Source\NewsSourceFetcherInterface;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class NewsImporterTest extends TestCase
{
    private function makeSource(bool $enabled = true): NewsSource
    {
        $source = new NewsSource('lenta', 'Lenta.ru', 'https://lenta.ru/rss/news');
        if (!$enabled) {
            $source->setEnabled(false);
        }
        return $source;
    }

    private function makeDto(string $uid): NewsItemDto
    {
        return new NewsItemDto(
            title: "News {$uid}",
            sourceUid: $uid,
            summary: 'Summary',
        );
    }

    public function testImportsNewItems(): void
    {
        $source = $this->makeSource();
        $dtos = [$this->makeDto('uid1'), $this->makeDto('uid2'), $this->makeDto('uid3')];

        $fetcher = $this->createStub(NewsSourceFetcherInterface::class);
        $fetcher->method('supports')->willReturn(true);
        $fetcher->method('fetch')->willReturn($dtos);

        $repo = $this->createStub(NewsRepository::class);
        $repo->method('existsBySourceUid')->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(3))->method('persist')->with($this->isInstanceOf(News::class));
        $em->expects($this->once())->method('flush');

        $importer = new NewsImporter([$fetcher], $repo, $em, new NullLogger());
        $count = $importer->import($source);

        $this->assertSame(3, $count);
    }

    public function testSkipsDuplicates(): void
    {
        $source = $this->makeSource();
        $dtos = [$this->makeDto('uid1'), $this->makeDto('uid2')];

        $fetcher = $this->createStub(NewsSourceFetcherInterface::class);
        $fetcher->method('supports')->willReturn(true);
        $fetcher->method('fetch')->willReturn($dtos);

        $repo = $this->createStub(NewsRepository::class);
        $repo->method('existsBySourceUid')->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->once())->method('flush');

        $importer = new NewsImporter([$fetcher], $repo, $em, new NullLogger());
        $count = $importer->import($source);

        $this->assertSame(0, $count);
    }

    public function testSkipsDuplicatesWithinSameBatch(): void
    {
        $source = $this->makeSource();
        // Same uid appears twice in one fetch — simulates duplicate guids in real RSS
        $dtos = [$this->makeDto('uid-dup'), $this->makeDto('uid-dup'), $this->makeDto('uid-unique')];

        $fetcher = $this->createStub(NewsSourceFetcherInterface::class);
        $fetcher->method('supports')->willReturn(true);
        $fetcher->method('fetch')->willReturn($dtos);

        $repo = $this->createStub(NewsRepository::class);
        $repo->method('existsBySourceUid')->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');

        $importer = new NewsImporter([$fetcher], $repo, $em, new NullLogger());
        $count = $importer->import($source);

        $this->assertSame(2, $count);
    }

    public function testNoFetcherForUnknownType(): void
    {
        $source = $this->makeSource();

        $fetcher = $this->createStub(NewsSourceFetcherInterface::class);
        $fetcher->method('supports')->willReturn(false);

        $repo = $this->createStub(NewsRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $importer = new NewsImporter([$fetcher], $repo, $em, new NullLogger());
        $count = $importer->import($source);

        $this->assertSame(0, $count);
    }
}
