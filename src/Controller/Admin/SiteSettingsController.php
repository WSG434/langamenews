<?php

namespace App\Controller\Admin;

use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/settings')]
class SiteSettingsController extends AbstractController
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $settings = $this->siteSettingsRepository->getCurrent();

        if ($request->isMethod('POST')) {
            $settings->setTelegramEnabled($request->request->getBoolean('telegram_enabled'));
            $this->em->flush();
            $this->addFlash('success', 'Settings saved.');
            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings.html.twig', [
            'settings' => $settings,
        ]);
    }
}
