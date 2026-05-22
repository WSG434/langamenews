<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ConfirmationCodeRepository;
use App\Repository\UserRepository;
use App\Repository\UserTelegramRepository;
use App\Service\Confirmation\ConfirmationService;
use App\Service\Telegram\TelegramSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TelegramLoginController extends AbstractController
{
    #[Route('/login/telegram', name: 'app_telegram_login', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $users,
        UserTelegramRepository $telegramRepo,
        ConfirmationService $confirmationService,
        TelegramSender $telegram,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $user = $users->findOneBy(['email' => $email]);

            if ($user !== null && $user->isVerified()) {
                $tg = $telegramRepo->findForUser($user);

                if ($tg !== null && $tg->isLinked()) {
                    $code = $confirmationService->generate($user);
                    $telegram->sendTo($tg->getChatId(), "Ваш код для входа: {$code->getCode()}");

                    return $this->redirectToRoute('app_telegram_login_verify', ['id' => $user->getId()]);
                }
            }

            // Не раскрываем причину отказа
            $this->addFlash('error', 'Не удалось отправить код. Убедитесь, что email верный и Telegram привязан.');
        }

        return $this->render('security/telegram_login.html.twig');
    }

    #[Route('/login/telegram/verify/{id}', name: 'app_telegram_login_verify', methods: ['GET', 'POST'])]
    public function verify(
        int $id,
        Request $request,
        UserRepository $users,
        ConfirmationCodeRepository $codes,
        ConfirmationService $confirmationService,
        Security $security,
    ): Response {
        $user = $users->find($id);
        if ($user === null) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            $input = trim($request->request->get('code', ''));
            $code = $codes->findActiveForUser($user);

            if ($code === null) {
                $this->addFlash('error', 'Код не найден или истёк. Запросите новый.');
                return $this->render('security/telegram_login_verify.html.twig', ['userId' => $id]);
            }

            try {
                $confirmationService->validate($code, $input);
                return $security->login($user, 'form_login', 'main')
                    ?? $this->redirectToRoute('app_news');
            } catch (\DomainException $e) {
                $messages = [
                    'expired' => 'Код истёк. Запросите новый.',
                    'too_many_attempts' => 'Слишком много попыток. Запросите новый код.',
                    'invalid' => 'Неверный код.',
                ];
                $this->addFlash('error', $messages[$e->getMessage()] ?? 'Неверный код.');
            }
        }

        return $this->render('security/telegram_login_verify.html.twig', ['userId' => $id]);
    }
}
