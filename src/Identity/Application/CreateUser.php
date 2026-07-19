<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Dto\PlainPassword;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;

final readonly class CreateUser
{
    public const int PASSWORD_MIN_LENGTH = PlainPassword::MIN_LENGTH;
    public const int PASSWORD_MAX_LENGTH = PlainPassword::MAX_LENGTH;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
    ) {
    }

    public function execute(string $login, #[\SensitiveParameter] string $plainPassword): User
    {
        $normalizedLogin = User::normalizeLogin($login);
        $password = PlainPassword::fromString($plainPassword);
        $user = User::register(
            $normalizedLogin,
            $this->passwordHasher->hash($password->value()),
        );

        $this->users->save($user);

        return $user;
    }
}

