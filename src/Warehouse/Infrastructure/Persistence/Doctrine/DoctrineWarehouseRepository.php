<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineWarehouseRepository implements WarehouseRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function all(): iterable
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('warehouse')
            ->from(Warehouse::class, 'warehouse')
            ->orderBy('warehouse.name', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    public function assignedToUser(int $userId): iterable
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('warehouse')
            ->from(Warehouse::class, 'warehouse')
            ->innerJoin('warehouse.users', 'assignedUser')
            ->where('assignedUser.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('warehouse.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isUserAssignedTo(Warehouse $warehouse, User $user): bool
    {
        $assignmentCount = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(warehouse.id)')
            ->from(Warehouse::class, 'warehouse')
            ->where('warehouse = :warehouse')
            ->andWhere(':user MEMBER OF warehouse.users')
            ->setParameter('warehouse', $warehouse)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return 0 < (int) $assignmentCount;
    }

    public function findByIds(iterable $ids): iterable
    {
        $identifiers = $this->identifierList($ids);

        if ([] === $identifiers) {
            return new \EmptyIterator();
        }

        return $this->entityManager
            ->createQueryBuilder()
            ->select('warehouse', 'warehouseUser')
            ->from(Warehouse::class, 'warehouse')
            ->leftJoin('warehouse.users', 'warehouseUser')
            ->where('warehouse.id IN (:ids)')
            ->setParameter('ids', $identifiers)
            ->getQuery()
            ->getResult();
    }

    public function find(int $id): ?Warehouse
    {
        $warehouse = $this->entityManager->find(Warehouse::class, $id);

        return $warehouse instanceof Warehouse ? $warehouse : null;
    }

    public function save(Warehouse $warehouse): void
    {
        $this->entityManager->persist($warehouse);
    }

    public function remove(Warehouse $warehouse): void
    {
        $this->entityManager->remove($warehouse);
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
