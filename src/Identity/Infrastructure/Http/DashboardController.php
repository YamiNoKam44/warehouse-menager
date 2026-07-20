<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Infrastructure\Http\Dto\DashboardViewData;
use App\Identity\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/', name: 'user_dashboard', methods: [Request::METHOD_GET])]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedException();
        }

        $viewData = new DashboardViewData($user->getUserIdentifier());

        return new Response($this->twig->render(
            'identity/dashboard.html.twig',
            $viewData->toArray(),
        ));
    }
}
