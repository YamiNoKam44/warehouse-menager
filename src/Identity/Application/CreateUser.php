<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Dto\PlainPassword;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Exception\PersistenceUniqueConstraintViolation;
use App\Shared\Application\Port\UnitOfWork;

final readonly class CreateUser
{
    public const int PASSWORD_MIN_LENGTH = PlainPassword::MIN_LENGTH;
    public const int PASSWORD_MAX_LENGTH = PlainPassword::MAX_LENGTH;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private UnitOfWork $unitOfWork,
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

        try {
            $this->unitOfWork->commit();
        } catch (PersistenceUniqueConstraintViolation $exception) {
            throw UserAlreadyExists::withLogin($user->login(), $exception);
        }

        return $user;
    }
}

