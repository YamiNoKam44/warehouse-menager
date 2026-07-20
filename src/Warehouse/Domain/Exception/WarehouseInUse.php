<?php

declare(strict_types=1);

namespace App\Warehouse\Domain\Exception;

final class WarehouseInUse extends \DomainException
{
    public static function create(?\Throwable $previous = null): self
    {
        return new self('Nie można usunąć magazynu użytego w operacji magazynowej.', previous: $previous);
    }
}
