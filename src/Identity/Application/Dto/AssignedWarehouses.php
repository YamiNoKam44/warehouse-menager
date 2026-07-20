<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Model\Warehouse;

/** @implements \IteratorAggregate<int, Warehouse> */
final readonly class AssignedWarehouses implements \IteratorAggregate
{
    /** @param list<Warehouse> $warehouses */
    private function __construct(private array $warehouses)
    {
    }

    /** @param iterable<Warehouse> $warehouses */
    public static function fromFound(
        AssignedWarehouseIds $requestedIds,
        iterable $warehouses,
    ): self {
        $warehousesById = [];

        foreach ($warehouses as $warehouse) {
            $id = $warehouse->id();

            if (null !== $id) {
                $warehousesById[$id] = $warehouse;
            }
        }

        $assignedWarehouses = [];

        foreach ($requestedIds as $id) {
            $assignedWarehouses[] = $warehousesById[$id]
                ?? throw WarehouseNotFound::withId($id);
        }

        return new self($assignedWarehouses);
    }


    public function getIterator(): \Traversable
    {
        yield from $this->warehouses;
    }
}
