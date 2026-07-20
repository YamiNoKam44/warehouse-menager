<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Http\Dto;

use App\Warehouse\Domain\Model\Warehouse;

final readonly class WarehouseIndexViewData
{
    /** @param iterable<Warehouse> $warehouses */
    public function __construct(private iterable $warehouses)
    {
    }

    /** @return array{warehouses: iterable<Warehouse>} */
    public function toArray(): array
    {
        return ['warehouses' => $this->warehouses];
    }
}
