<?php

namespace App\EventListener;

use App\Entity\Notification;
use App\Event\UserRegisteredEvent;
use App\Repository\NotificationRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class NotificationListener
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    #[AsEventListener]
    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $this->notifications->save(new Notification('user_registered', [
            'email' => $event->user->getEmail(),
        ]));
    }

    #[AsEventListener]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        $email = method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user;

        $this->notifications->save(new Notification('user_logged_in', [
            'email' => $email,
        ]));
    }
}
