<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserNotificationSetting;
use App\Entity\UserTelegram;
use App\Repository\UserNotificationSettingRepository;
use App\Repository\UserRepository;
use App\Repository\UserTelegramRepository;
use App\Service\Telegram\TelegramLinkFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly UserNotificationSettingRepository $settingRepository,
        private readonly UserTelegramRepository $telegramRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TelegramLinkFactory $telegramLinks,
    ) {}

    #[Route('', name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $setting = $this->settingRepository->findForUser($user);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'delete_account') {
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                $this->em->remove($user);
                $this->em->flush();
                return $this->redirectToRoute('app_home');
            }

            $email = trim($request->request->get('email', ''));
            $plainPassword = $request->request->get('password', '');

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Некорректный email.');
                return $this->redirectToRoute('app_settings');
            }

            if ($email !== '' && $email !== $user->getEmail()) {
                $existing = $this->userRepository->findOneBy(['email' => $email]);
                if ($existing !== null) {
                    $this->addFlash('error', 'Этот email уже занят.');
                    return $this->redirectToRoute('app_settings');
                }
                $user->setEmail($email);
            }
            if ($plainPassword !== '') {
                $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
            }

            if ($setting === null) {
                $setting = new UserNotificationSetting($user);
                $this->em->persist($setting);
            }
            $setting->setTelegramEnabled($request->request->getBoolean('telegram_enabled'));

            $this->em->flush();
            $this->addFlash('success', 'Настройки сохранены.');
            return $this->redirectToRoute('app_settings');
        }

        $userTelegram = $this->telegramRepository->findForUser($user);
        if ($userTelegram === null) {
            $userTelegram = new UserTelegram($user);
            $this->em->persist($userTelegram);
            $this->em->flush();
        }

        return $this->render('settings/index.html.twig', [
            'telegramEnabled' => $setting === null || $setting->isTelegramEnabled(),
            'telegramLinked'  => $userTelegram->isLinked(),
            'telegramLinkUrl' => $this->telegramLinks->linkUrl($userTelegram),
        ]);
    }
}
