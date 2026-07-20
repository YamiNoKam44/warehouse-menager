<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Persistence\Doctrine;

use App\Warehouse\Domain\Exception\WarehouseInUse;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineWarehouseRepository implements WarehouseRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function all(): iterable
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('warehouse')
            ->from(Warehouse::class, 'warehouse')
            ->orderBy('warehouse.name', 'ASC')
            ->getQuery();

        foreach ($query->toIterable() as $warehouse) {
            if ($warehouse instanceof Warehouse) {
                yield $warehouse;
            }
        }
    }

    public function assignedToUser(int $userId): iterable
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('warehouse')
            ->from(Warehouse::class, 'warehouse')
            ->innerJoin('warehouse.users', 'assignedUser')
            ->where('assignedUser.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('warehouse.name', 'ASC')
            ->getQuery();

        foreach ($query->getResult() as $warehouse) {
            if ($warehouse instanceof Warehouse) {
                yield $warehouse;
            }
        }
    }

    public function find(int $id): ?Warehouse
    {
        $warehouse = $this->entityManager->find(Warehouse::class, $id);

        return $warehouse instanceof Warehouse ? $warehouse : null;
    }

    public function save(Warehouse $warehouse): void
    {
        $this->entityManager->persist($warehouse);
        $this->entityManager->flush();
    }

    public function remove(Warehouse $warehouse): void
    {
        try {
            $this->entityManager->remove($warehouse);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $exception) {
            throw WarehouseInUse::create($exception);
        }
    }
}
