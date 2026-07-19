<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Exception\InvalidLogin;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(int $id): ?User
    {
        $user = $this->entityManager->find(User::class, $id);

        return $user instanceof User ? $user : null;
    }

    public function findByLogin(string $login): ?User
    {
        try {
            $normalizedLogin = User::normalizeLogin($login);
        } catch (InvalidLogin) {
            return null;
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['login' => $normalizedLogin]);

        return $user instanceof User ? $user : null;
    }

    public function save(User $user): void
    {
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw UserAlreadyExists::withLogin($user->login(), $exception);
        }
    }
}
