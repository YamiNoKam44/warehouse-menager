<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Infrastructure\Http\Dto\LoginViewData;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

final readonly class LoginController
{
    public function __construct(
        private Environment $twig,
        private AuthenticationUtils $authenticationUtils,
        private Security $security,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route(
        '/login',
        name: 'app_login',
        methods: [Request::METHOD_GET, Request::METHOD_POST],
    )]
    public function __invoke(): Response
    {
        if (null !== $this->security->getUser()) {
            return new RedirectResponse($this->urls->generate('user_dashboard'));
        }

        $viewData = new LoginViewData(
            $this->authenticationUtils->getLastUsername(),
            null !== $this->authenticationUtils->getLastAuthenticationError(),
        );

        return new Response($this->twig->render(
            'identity/login.html.twig',
            $viewData->toArray(),
        ));
    }
}
