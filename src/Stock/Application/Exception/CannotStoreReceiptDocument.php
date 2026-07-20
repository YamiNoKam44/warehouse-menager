<?php

declare(strict_types=1);

namespace App\Stock\Application\Exception;

final class CannotStoreReceiptDocument extends \RuntimeException
{
    public static function create(?\Throwable $previous = null): self
    {
        return new self('Nie udało się bezpiecznie zapisać załączonego dokumentu.', 0, $previous);
    }
}
