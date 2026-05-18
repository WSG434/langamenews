<?php

namespace App\DataFixtures;

use App\Entity\NewsSource;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NewsSourceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sources = [
            ['lenta', 'Lenta.ru', 'https://lenta.ru/rss/news'],
            ['habr', 'Habr', 'https://habr.com/ru/rss/news/'],
        ];

        foreach ($sources as [$code, $name, $url]) {
            $existing = $manager->getRepository(NewsSource::class)->findOneBy(['code' => $code]);
            if ($existing !== null) {
                continue;
            }
            $manager->persist(new NewsSource($code, $name, $url));
        }

        $manager->flush();
    }
}
