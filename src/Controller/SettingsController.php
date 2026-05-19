<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserNotificationSetting;
use App\Repository\UserNotificationSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly UserNotificationSettingRepository $settingRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $setting = $this->settingRepository->findForUser($user);

        if ($request->isMethod('POST')) {
            if ($setting === null) {
                $setting = new UserNotificationSetting($user);
                $this->em->persist($setting);
            }
            $setting->setTelegramEnabled($request->request->getBoolean('telegram_enabled'));
            $this->em->flush();
            $this->addFlash('success', 'Settings saved.');
            return $this->redirectToRoute('app_settings');
        }

        return $this->render('settings/index.html.twig', [
            'telegramEnabled' => $setting === null || $setting->isTelegramEnabled(),
        ]);
    }
}
