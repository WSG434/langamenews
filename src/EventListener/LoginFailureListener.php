<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener]
class LoginFailureListener
{
    public function __construct(private readonly RouterInterface $router) {}

    public function __invoke(LoginFailureEvent $event): void
    {
        $exception = $event->getException();

        if (!$exception instanceof CustomUserMessageAuthenticationException) {
            return;
        }

        if ($exception->getMessageKey() !== 'account_not_verified') {
            return;
        }

        $params = $exception->getMessageData();
        $userId = $params['userId'] ?? null;

        if ($userId === null) {
            return;
        }

        $url = $this->router->generate('app_confirm', ['id' => $userId]);
        $event->setResponse(new RedirectResponse($url));
    }
}
