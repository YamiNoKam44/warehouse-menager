<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Exception;

final class WarehouseNotFound extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Magazyn o identyfikatorze %d nie istnieje.', $id));
    }
}
