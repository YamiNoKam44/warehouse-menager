<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Exception\InvalidLogin;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function all(): iterable
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->orderBy('user.login', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    public function findByIds(iterable $ids): iterable
    {
        $identifiers = $this->identifierList($ids);

        if ([] === $identifiers) {
            return new \EmptyIterator();
        }

        return $this->entityManager
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id IN (:ids)')
            ->setParameter('ids', $identifiers)
            ->getQuery()
            ->toIterable();
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
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.login = :login')
            ->setParameter('login', $normalizedLogin)
            ->getQuery()
            ->getOneOrNullResult();

        return $user instanceof User ? $user : null;
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
    }

    /**
     * @param iterable<int> $ids
     *
     * @return list<int>
     */
    private function identifierList(iterable $ids): array
    {
        $identifiers = [];

        foreach ($ids as $id) {
            $identifiers[] = $id;
        }

        return $identifiers;
    }
}
