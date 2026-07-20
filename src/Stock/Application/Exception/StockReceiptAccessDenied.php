<?php

declare(strict_types=1);

namespace App\Stock\Application\Exception;

final class StockReceiptAccessDenied extends \RuntimeException
{
    public static function toWarehouse(): self
    {
        return new self('Brak dostępu do wybranego magazynu.');
    }
}
