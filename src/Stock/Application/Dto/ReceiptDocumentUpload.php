<?php

declare(strict_types=1);

namespace App\Stock\Application\Dto;

use App\Stock\Domain\Model\ReceiptDocumentType;

final readonly class ReceiptDocumentUpload
{
    public const int MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public ReceiptDocumentType $type,
    ) {
    }
}
