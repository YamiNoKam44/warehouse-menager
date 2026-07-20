<?php

declare(strict_types=1);

namespace App\Warehouse\Domain\Repository;

use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Model\Warehouse;

interface WarehouseRepository
{
    /** @return iterable<Warehouse> */
    public function all(): iterable;

    /** @return iterable<Warehouse> */
    public function assignedToUser(int $userId): iterable;

    public function isUserAssignedTo(Warehouse $warehouse, User $user): bool;

    /**
     * @param iterable<int> $ids
     *
     * @return iterable<Warehouse>
     */
    public function findByIds(iterable $ids): iterable;

    public function find(int $id): ?Warehouse;

    public function save(Warehouse $warehouse): void;

    public function remove(Warehouse $warehouse): void;
}
