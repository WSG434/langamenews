<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NewsRepository;
use App\Repository\NewsSourceRepository;
use App\Repository\UserNewsSourcePreferenceRepository;
use App\Service\Search\Highlighter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/news')]
class NewsController extends AbstractController
{
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private readonly NewsSourceRepository $sourceRepository,
        private readonly UserNewsSourcePreferenceRepository $preferenceRepository,
        private readonly Highlighter $highlighter,
    ) {}

    #[Route('', name: 'app_news', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $excluded = $this->preferenceRepository->findDisabledSourceCodes($user);
        $sources = $this->sourceRepository->findBy(['enabled' => true], ['name' => 'ASC']);

        return $this->render('news/index.html.twig', [
            'news' => $this->newsRepository->findLatest(10, 0, $excluded),
            'sources' => $sources,
            'disabledSourceCodes' => $excluded,
        ]);
    }

    #[Route('/more', name: 'app_news_more', methods: ['GET'])]
    public function more(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $excluded = $this->preferenceRepository->findDisabledSourceCodes($user);
        $offset = max(0, $request->query->getInt('offset', 10));
        $items = $this->newsRepository->findLatest(20, $offset, $excluded);

        return $this->json(array_map($this->formatNewsEntity(...), $items));
    }

    #[Route('/search', name: 'app_news_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $excluded = $this->preferenceRepository->findDisabledSourceCodes($user);
        $q = trim($request->query->getString('q'));

        if ($q === '') {
            return $this->json(array_map(
                $this->formatNewsEntity(...),
                $this->newsRepository->findLatest(10, 0, $excluded)
            ));
        }

        return $this->json(array_map(fn (array $row) => [
            'id' => $row['id'],
            'title' => $this->highlighter->highlight($row['title'], $q),
            'summary' => $this->highlighter->highlight($row['summary'] ?? '', $q),
            'publishedAt' => $row['published_at'] ? (new \DateTimeImmutable($row['published_at']))->format('d.m.Y H:i') : null,
            'source' => $row['source'],
            'url' => $row['url'] ?? null,
            'imageUrl' => $row['image_url'] ?? null,
        ], $this->newsRepository->searchFullText($q, 20, $excluded)));
    }

    private function formatNewsEntity(\App\Entity\News $news): array
    {
        return [
            'id' => $news->getId(),
            'title' => htmlspecialchars($news->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'summary' => htmlspecialchars($news->getSummary() ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'publishedAt' => $news->getPublishedAt()?->format('d.m.Y H:i'),
            'source' => $news->getSource(),
            'url' => $news->getUrl(),
            'imageUrl' => $news->getImageUrl(),
        ];
    }
}
