<?php

declare(strict_types=1);

namespace App\Stock\Application\Dto;

use App\Stock\Domain\Model\ReceiptDocumentType;

final readonly class StoredReceiptDocument
{
    public function __construct(
        public string $storedName,
        public string $originalName,
        public ReceiptDocumentType $type,
    ) {
    }
}
