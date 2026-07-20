<?php

declare(strict_types=1);

namespace App\Stock\Application\Dto;

use App\Stock\Domain\Exception\InvalidStockReceiptData;
use App\Stock\Domain\Model\StockReceipt;

/** @implements \IteratorAggregate<int, ReceiptDocumentUpload> */
final readonly class ReceiptDocumentUploads implements \IteratorAggregate
{
    /** @param list<ReceiptDocumentUpload> $uploads */
    private function __construct(private array $uploads)
    {
    }

    /** @param iterable<ReceiptDocumentUpload> $uploads */
    public static function fromIterable(iterable $uploads): self
    {
        $validatedUploads = [];

        foreach ($uploads as $upload) {
            $validatedUploads[] = $upload;

            if (StockReceipt::MAX_DOCUMENTS < count($validatedUploads)) {
                throw InvalidStockReceiptData::becauseOfDocumentLimit(StockReceipt::MAX_DOCUMENTS);
            }
        }

        return new self($validatedUploads);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->uploads;
    }
}
