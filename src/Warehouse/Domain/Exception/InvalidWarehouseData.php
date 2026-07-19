<?php

declare(strict_types=1);

namespace App\Warehouse\Domain\Exception;

final class InvalidWarehouseData extends \DomainException
{
    public static function becauseOfNameLength(int $minimumLength, int $maximumLength): self
    {
        return new self(sprintf(
            'Nazwa magazynu musi mieć od %d do %d znaków.',
            $minimumLength,
            $maximumLength,
        ));
    }

    public static function becauseOfInvalidUserId(): self
    {
        return new self('Wybrano nieprawidłowego użytkownika.');
    }

    public static function becauseUserDoesNotExist(int $userId): self
    {
        return new self(sprintf('Użytkownik o identyfikatorze %d nie istnieje.', $userId));
    }
}
