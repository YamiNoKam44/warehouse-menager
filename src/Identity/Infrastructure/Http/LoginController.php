<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class LoginController
{
    public function __construct(
        private Environment $twig,
        private AuthenticationUtils $authenticationUtils,
        private Security $security,
        private UrlGeneratorInterface $urls,
    ) {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function __invoke(): Response
    {
        if (null !== $this->security->getUser()) {
            return new RedirectResponse($this->urls->generate('user_dashboard'));
        }

        return new Response($this->twig->render('identity/login.html.twig', [
            'last_username' => $this->authenticationUtils->getLastUsername(),
            'authentication_failed' => null !== $this->authenticationUtils->getLastAuthenticationError(),
        ]));
    }
}
