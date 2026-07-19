<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

final readonly class UserData
{
    public function __construct(
        public string $login,
        public ?PlainPassword $password,
        public AssignedWarehouseIds $assignedWarehouseIds,
    ) {
    }
}
