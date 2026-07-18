<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;

final readonly class CreateUser
{
    public const int PASSWORD_MIN_LENGTH = 8;
    public const int PASSWORD_MAX_LENGTH = 4096;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
    ) {
    }

    public function execute(string $login, #[\SensitiveParameter] string $plainPassword): User
    {
        $normalizedLogin = User::normalizeLogin($login);
        $passwordLength = strlen($plainPassword);

        if (self::PASSWORD_MIN_LENGTH > $passwordLength || self::PASSWORD_MAX_LENGTH < $passwordLength) {
            throw InvalidPassword::becauseOfLength(self::PASSWORD_MIN_LENGTH, self::PASSWORD_MAX_LENGTH);
        }

        $user = User::register(
            $normalizedLogin,
            $this->passwordHasher->hash($plainPassword),
        );

        $this->users->save($user);

        return $user;
    }
}

