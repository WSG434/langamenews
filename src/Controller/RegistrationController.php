<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserTelegram;
use App\Form\RegistrationFormType;
use App\Message\SendConfirmationCodeMessage;
use App\Service\Confirmation\ConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        ConfirmationService $confirmationService,
        MessageBusInterface $bus,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $handle = $form->get('telegramHandle')->getData();
            if ($handle) {
                $user->setTelegramHandle(ltrim($handle, '@'));
            }

            $em->persist($user);
            $em->persist(new UserTelegram($user));
            $em->flush();

            $code = $confirmationService->generate($user);
            $bus->dispatch(new SendConfirmationCodeMessage($code->getId()));

            $request->getSession()->set('pending_confirmation_user_id', $user->getId());
            return $this->redirectToRoute('app_confirm', ['id' => $user->getId()]);
        }

        return $this->render('registration/register.html.twig', ['form' => $form]);
    }
}
