<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Model\UserRole;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private int $id,
        private string $login,
        private string $passwordHash,
        private UserRole $role,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        // UserInterface wymaga tablicy; domena przechowuje kontrolowany enum UserRole.
        return [$this->role->value];
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }
}

