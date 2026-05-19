<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\SendConfirmationCodeMessage;
use App\Repository\ConfirmationCodeRepository;
use App\Repository\UserRepository;
use App\Service\Confirmation\ConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ConfirmationController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ConfirmationCodeRepository $codes,
        private readonly ConfirmationService $confirmationService,
        private readonly MessageBusInterface $bus,
        private readonly EntityManagerInterface $em,
        private readonly RateLimiterFactory $codeSendLimiter,
        private readonly RateLimiterFactory $codeAttemptsLimiter,
        private readonly Security $security,
    ) {}

    #[Route('/register/confirm/{id}', name: 'app_confirm', methods: ['GET', 'POST'])]
    public function confirm(int $id, Request $request): Response
    {
        $user = $this->findUserOr404($id);

        if ($user->isVerified()) {
            $this->addFlash('success', 'Your account is already verified. Please log in.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $attemptsLimiter = $this->codeAttemptsLimiter->create("code_attempts_{$id}");
            if (!$attemptsLimiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Too many attempts. Please request a new code.');
                return $this->render('registration/confirm.html.twig', ['userId' => $id]);
            }

            $code = $this->codes->findActiveForUser($user);
            if ($code === null) {
                $this->addFlash('error', 'Code expired or not found. Please request a new one.');
                return $this->render('registration/confirm.html.twig', ['userId' => $id]);
            }

            $input = trim($request->request->get('code', ''));

            try {
                $this->confirmationService->validate($code, $input);
                $this->addFlash('success', 'Account confirmed! Welcome!');
                return $this->security->login($user, 'form_login', 'main')
                    ?? $this->redirectToRoute('app_news');
            } catch (\DomainException $e) {
                $messages = [
                    'expired' => 'Code has expired. Please request a new one.',
                    'too_many_attempts' => 'Too many invalid attempts. Please request a new code.',
                    'invalid' => 'Invalid code. Please try again.',
                ];
                $this->addFlash('error', $messages[$e->getMessage()] ?? 'Invalid code.');
            }
        }

        return $this->render('registration/confirm.html.twig', ['userId' => $id]);
    }

    #[Route('/register/confirm/{id}/resend', name: 'app_confirm_resend', methods: ['POST'])]
    public function resend(int $id, Request $request): Response
    {
        $user = $this->findUserOr404($id);

        if ($user->isVerified()) {
            return $this->redirectToRoute('app_login');
        }

        $sendLimiter = $this->codeSendLimiter->create("code_send_{$id}_" . $request->getClientIp());
        if (!$sendLimiter->consume(1)->isAccepted()) {
            $this->addFlash('error', 'Too many resend attempts. Please wait a few minutes.');
            return $this->redirectToRoute('app_confirm', ['id' => $id]);
        }

        $existing = $this->codes->findActiveForUser($user);
        if ($existing !== null) {
            $existing->markFailed();
            $this->em->flush();
        }

        $code = $this->confirmationService->generate($user);
        $this->bus->dispatch(new SendConfirmationCodeMessage($code->getId()));

        $this->addFlash('info', 'A new confirmation code has been sent.');
        return $this->redirectToRoute('app_confirm', ['id' => $id]);
    }

    private function findUserOr404(int $id): User
    {
        $user = $this->users->find($id);
        if ($user === null) {
            throw $this->createNotFoundException("User not found.");
        }
        return $user;
    }
}
