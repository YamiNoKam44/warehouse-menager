<?php

declare(strict_types=1);

namespace App\Stock\Application\Dto;

use App\Stock\Domain\Model\VatRate;

final readonly class ReceiptData
{
    public function __construct(
        public int $warehouseId,
        public int $articleId,
        public string $quantity,
        public VatRate $vatRate,
        public string $unitNetPrice,
        public ReceiptDocumentUploads $documents,
    ) {
    }
}
