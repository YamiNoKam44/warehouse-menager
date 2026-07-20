<?php

declare(strict_types=1);

namespace App\Stock\Application\Dto;

final readonly class IssueData
{
    public function __construct(
        public int $warehouseId,
        public int $articleId,
        public string $quantity,
    ) {
    }
}
