<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NewsRepository;
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
        private readonly UserNewsSourcePreferenceRepository $preferenceRepository,
        private readonly Highlighter $highlighter,
    ) {}

    #[Route('', name: 'app_news', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $excluded = $this->preferenceRepository->findDisabledSourceCodes($user);

        return $this->render('news/index.html.twig', [
            'news' => $this->newsRepository->findLatest(10, 0, $excluded),
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

        return $this->json(array_map(fn ($n) => [
            'id' => $n->getId(),
            'title' => htmlspecialchars($n->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'summary' => htmlspecialchars($n->getSummary() ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'publishedAt' => $n->getPublishedAt()?->format('d.m.Y H:i'),
            'source' => $n->getSource(),
            'url' => $n->getUrl(),
            'imageUrl' => $n->getImageUrl(),
        ], $items));
    }

    #[Route('/search', name: 'app_news_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $excluded = $this->preferenceRepository->findDisabledSourceCodes($user);
        $q = trim($request->query->getString('q'));

        if ($q === '') {
            $rows = array_map(
                fn ($n) => [
                    'id' => $n->getId(),
                    'title' => htmlspecialchars($n->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'summary' => htmlspecialchars($n->getSummary() ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'publishedAt' => $n->getPublishedAt()?->format('d.m.Y H:i'),
                    'source' => $n->getSource(),
                    'url' => $n->getUrl(),
                    'imageUrl' => $n->getImageUrl(),
                ],
                $this->newsRepository->findLatest(10, 0, $excluded)
            );
            return $this->json($rows);
        }

        $rows = $this->newsRepository->searchFullText($q, 20, $excluded);

        $result = array_map(fn (array $row) => [
            'id' => $row['id'],
            'title' => $this->highlighter->highlight($row['title'], $q),
            'summary' => $this->highlighter->highlight($row['summary'] ?? '', $q),
            'publishedAt' => $row['published_at'] ? (new \DateTimeImmutable($row['published_at']))->format('d.m.Y H:i') : null,
            'source' => $row['source'],
            'url' => $row['url'] ?? null,
            'imageUrl' => $row['image_url'] ?? null,
        ], $rows);

        return $this->json($result);
    }
}
