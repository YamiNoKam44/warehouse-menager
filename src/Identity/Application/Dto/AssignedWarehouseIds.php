<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

use App\Warehouse\Domain\Model\Warehouse;

/** @implements \IteratorAggregate<int, int> */
final readonly class AssignedWarehouseIds implements \IteratorAggregate
{
    /** @param list<int> $ids */
    private function __construct(private array $ids)
    {
    }

    public static function none(): self
    {
        return new self([]);
    }

    /** @param iterable<Warehouse> $warehouses */
    public static function fromWarehouses(iterable $warehouses): self
    {
        $ids = [];

        foreach ($warehouses as $warehouse) {
            $id = $warehouse->id();

            if (null === $id) {
                throw new \LogicException('Nie można przypisać niezapisanego magazynu.');
            }

            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return new self($ids);
    }

    public function mergedWith(self $other): self
    {
        $ids = $this->ids;

        foreach ($other as $id) {
            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return new self($ids);
    }

    public function contains(Warehouse $warehouse): bool
    {
        $id = $warehouse->id();

        return null !== $id && in_array($id, $this->ids, true);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->ids;
    }
}
