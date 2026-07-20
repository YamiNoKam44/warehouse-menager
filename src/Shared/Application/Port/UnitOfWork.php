<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

interface UnitOfWork
{
    public function commit(): void;
}
