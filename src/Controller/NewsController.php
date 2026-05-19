<?php

namespace App\Controller;

use App\Repository\NewsRepository;
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
        private readonly Highlighter $highlighter,
    ) {}

    #[Route('', name: 'app_news', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('news/index.html.twig', [
            'news' => $this->newsRepository->findLatest(10),
        ]);
    }

    #[Route('/search', name: 'app_news_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->query->getString('q'));

        if ($q === '') {
            $rows = array_map(
                fn ($n) => [
                    'id' => $n->getId(),
                    'title' => htmlspecialchars($n->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'summary' => htmlspecialchars($n->getSummary() ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'publishedAt' => $n->getPublishedAt()?->format('Y-m-d H:i'),
                    'source' => $n->getSource(),
                ],
                $this->newsRepository->findLatest(10)
            );
            return $this->json($rows);
        }

        $rows = $this->newsRepository->searchFullText($q);

        $result = array_map(fn (array $row) => [
            'id' => $row['id'],
            'title' => $this->highlighter->highlight($row['title'], $q),
            'summary' => $this->highlighter->highlight($row['summary'] ?? '', $q),
            'publishedAt' => $row['published_at'] ? (new \DateTimeImmutable($row['published_at']))->format('Y-m-d H:i') : null,
            'source' => $row['source'],
        ], $rows);

        return $this->json($result);
    }
}
