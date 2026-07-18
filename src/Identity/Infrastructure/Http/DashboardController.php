<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

final readonly class DashboardController
{
    public function __construct(
        private Environment $twig,
        private Security $security,
    ) {
    }

    #[Route('/', name: 'user_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedException();
        }

        return new Response($this->twig->render('identity/dashboard.html.twig', [
            'login' => $user->getUserIdentifier(),
        ]));
    }
}
