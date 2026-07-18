<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

final class InvalidLogin extends \DomainException
{
    public static function fromString(string $login): self
    {
        return new self(sprintf('Login "%s" ma nieprawidłowy format.', $login));
    }
}
