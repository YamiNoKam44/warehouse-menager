<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

final class UserAlreadyExists extends \DomainException
{
    public static function withLogin(string $login, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Użytkownik o loginie "%s" już istnieje.', $login), 0, $previous);
    }
}
