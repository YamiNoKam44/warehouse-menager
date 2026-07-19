<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Persistence\Doctrine;

use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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
        $this->entityManager->remove($warehouse);
        $this->entityManager->flush();
    }
}
