<?php

declare(strict_types=1);

namespace App\Stock\Domain\Model;

use App\Stock\Domain\Exception\InvalidStockReceiptData;

enum ReceiptDocumentType: string
{
    case PDF = 'pdf';
    case XML = 'xml';

    public static function fromExtension(string $extension): self
    {
        return self::tryFrom(strtolower(trim($extension)))
            ?? throw InvalidStockReceiptData::becauseOfDocumentType();
    }
}
