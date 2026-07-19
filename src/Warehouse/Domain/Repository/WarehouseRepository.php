<?php

declare(strict_types=1);

namespace App\Warehouse\Domain\Repository;

use App\Warehouse\Domain\Model\Warehouse;

interface WarehouseRepository
{
    /** @return iterable<Warehouse> */
    public function all(): iterable;

    /** @return iterable<Warehouse> */
    public function assignedToUser(int $userId): iterable;

    public function find(int $id): ?Warehouse;

    public function save(Warehouse $warehouse): void;

    public function remove(Warehouse $warehouse): void;
}
