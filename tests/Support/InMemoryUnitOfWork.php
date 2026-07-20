<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Port\UnitOfWork;

final class InMemoryUnitOfWork implements UnitOfWork
{
    private int $commitCount = 0;

    public function commit(): void
    {
        ++$this->commitCount;
    }

    public function commitCount(): int
    {
        return $this->commitCount;
    }
}
