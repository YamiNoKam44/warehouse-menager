<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Dto;

final readonly class WarehouseData
{
    public function __construct(
        public string $name,
        public AssignedUserIds $assignedUserIds,
    ) {
    }
}
