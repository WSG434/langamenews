<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\UserRegisteredEvent;
use App\Notification\NotificationDispatcher;
use App\Notification\NotificationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class NotificationListener
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    #[AsEventListener]
    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $this->dispatcher->dispatch(new NotificationMessage('user_registered', [
            'email' => $event->user->getEmail(),
            'userId' => $event->user->getId(),
        ]));
    }

    #[AsEventListener]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        if (!$user instanceof User) {
            return;
        }
        $label = (string) $user;
        $userId = $user->getId();

        $this->dispatcher->dispatch(new NotificationMessage('user_logged_in', [
            'email' => $label,
            'userId' => $userId,
        ]));
    }
}
