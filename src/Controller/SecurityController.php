<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return new Response('Login page — coming in stage 1', 200);
    }

    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return new Response('Register page — coming in stage 1', 200);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Intercepted by security firewall.');
    }
}
