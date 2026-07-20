<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class PersistenceUniqueConstraintViolation extends \RuntimeException
{
    public static function fromPrevious(\Throwable $previous): self
    {
        return new self('Naruszono unikalność danych.', previous: $previous);
    }
}
