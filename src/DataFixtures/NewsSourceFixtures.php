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
            // RSS
            ['lenta',       'Lenta.ru',       NewsSource::TYPE_RSS,      'https://lenta.ru/rss/news'],
            ['habr',        'Habr',           NewsSource::TYPE_RSS,      'https://habr.com/ru/rss/news/'],
            // JSON API
            ['hackernews',  'Hacker News',    NewsSource::TYPE_JSON_API, 'https://hacker-news.firebaseio.com/v0/topstories.json'],
            ['newsapi',     'NewsAPI',         NewsSource::TYPE_JSON_API, 'https://newsapi.org/v2/top-headlines?country=us&pageSize=20'],
            ['guardian',    'The Guardian',   NewsSource::TYPE_JSON_API, 'https://content.guardianapis.com/search?page-size=20'],
            // HTML
            ['3dnews',      '3DNews',         NewsSource::TYPE_HTML,     'https://3dnews.ru/news/'],
            ['mkru',        'Московский Комсомолец', NewsSource::TYPE_HTML, 'https://www.mk.ru/news/'],
        ];

        foreach ($sources as [$code, $name, $type, $url]) {
            $existing = $manager->getRepository(NewsSource::class)->findOneBy(['code' => $code]);
            if ($existing !== null) {
                continue;
            }
            $manager->persist(new NewsSource($code, $name, $url, $type));
        }

        $manager->flush();
    }
}
