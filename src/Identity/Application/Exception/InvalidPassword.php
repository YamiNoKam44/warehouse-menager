<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

final class InvalidPassword extends \DomainException
{
    public static function becauseOfLength(int $minimumLength, int $maximumLength): self
    {
        return new self(sprintf(
            'Hasło musi mieć od %d do %d znaków.',
            $minimumLength,
            $maximumLength,
        ));
    }
}

