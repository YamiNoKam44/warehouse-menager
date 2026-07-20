<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LogoutController
{
    #[Route('/logout', name: 'app_logout', methods: [Request::METHOD_POST])]
    public function __invoke(): never
    {
        throw new \LogicException('Żądanie wylogowania powinno zostać przechwycone przez firewall.');
    }
}
