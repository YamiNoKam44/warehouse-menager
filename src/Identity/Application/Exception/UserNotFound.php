<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

final class UserNotFound extends \RuntimeException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Użytkownik o identyfikatorze %d nie istnieje.', $id));
    }
}
