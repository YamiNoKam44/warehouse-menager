<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class PersistenceForeignKeyConstraintViolation extends \RuntimeException
{
    public static function fromPrevious(\Throwable $previous): self
    {
        return new self('Nie można zmienić danych powiązanych z innymi rekordami.', previous: $previous);
    }
}
