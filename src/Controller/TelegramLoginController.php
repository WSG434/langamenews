<?php

namespace App\Controller;

use App\Repository\TelegramLoginTokenRepository;
use App\Repository\UserTelegramRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TelegramLoginController extends AbstractController
{
    public function __construct(private readonly string $botName) {}

    #[Route('/login/telegram', name: 'app_telegram_login', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        TelegramLoginTokenRepository $tokens,
        UserTelegramRepository $telegramRepo,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        if ($request->isMethod('POST')) {
            $input = trim($request->request->get('code', ''));
            $token = $tokens->findValidByCode($input);

            if ($token === null) {
                $this->addFlash('error', 'Неверный или истёкший код.');
                return $this->render('security/telegram_login.html.twig', ['botName' => $this->botName]);
            }

            $userTelegram = $telegramRepo->findByChatId($token->getChatId());
            if ($userTelegram === null) {
                $this->addFlash('error', 'Аккаунт не найден.');
                return $this->render('security/telegram_login.html.twig', ['botName' => $this->botName]);
            }

            $token->markUsed();
            $em->flush();

            return $security->login($userTelegram->getUser(), 'form_login', 'main')
                ?? $this->redirectToRoute('app_news');
        }

        return $this->render('security/telegram_login.html.twig', ['botName' => $this->botName]);
    }
}
