<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserNewsSourcePreference;
use App\Repository\NewsSourceRepository;
use App\Repository\UserNewsSourcePreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/news/sources')]
class NewsSourceController extends AbstractController
{
    public function __construct(
        private readonly NewsSourceRepository $sourceRepository,
        private readonly UserNewsSourcePreferenceRepository $preferenceRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_news_sources', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $sources = $this->sourceRepository->findBy(['enabled' => true], ['name' => 'ASC']);
        $preferences = $this->preferenceRepository->findForUser($user);

        $prefMap = [];
        foreach ($preferences as $pref) {
            $prefMap[$pref->getNewsSource()->getId()] = $pref->isEnabled();
        }

        return $this->render('news/sources.html.twig', [
            'sources' => $sources,
            'prefMap' => $prefMap,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_news_sources_toggle', methods: ['POST'])]
    public function toggle(int $id, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('toggle_source', $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();
        $source = $this->sourceRepository->find($id);

        if ($source === null || !$source->isEnabled()) {
            return $this->json(['error' => 'Source not found'], Response::HTTP_NOT_FOUND);
        }

        $pref = $this->preferenceRepository->findOneForUserAndSource($user, $source);

        if ($pref === null) {
            // Default is enabled=true, so first toggle = disable
            $pref = new UserNewsSourcePreference($user, $source, false);
            $this->em->persist($pref);
        } else {
            $pref->setEnabled(!$pref->isEnabled());
        }

        $this->em->flush();

        return $this->json(['enabled' => $pref->isEnabled()]);
    }
}
