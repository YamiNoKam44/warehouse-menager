<?php

declare(strict_types=1);

namespace App\Stock\Domain\Model;

use App\Stock\Domain\Exception\InvalidStockReceiptData;

final class ReceiptDocument
{
    public const int ORIGINAL_NAME_MAX_LENGTH = 255;
    public const int STORED_NAME_MAX_LENGTH = 64;

    private const string STORED_NAME_PATTERN = '/\A[a-f0-9]{32}\.(pdf|xml)\z/';

    private ?int $id;

    private function __construct(
        private readonly StockReceipt $receipt,
        private string $storedName,
        private string $originalName,
        private readonly ReceiptDocumentType $type,
    ) {
        $this->id = null;
        $this->storedName = self::validateStoredName($storedName, $type);
        $this->originalName = self::normalizeOriginalName($originalName);
    }

    public static function create(
        StockReceipt $receipt,
        string $storedName,
        string $originalName,
        ReceiptDocumentType $type,
    ): self {
        return new self($receipt, $storedName, $originalName, $type);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function receipt(): StockReceipt
    {
        return $this->receipt;
    }

    public function storedName(): string
    {
        return $this->storedName;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function type(): ReceiptDocumentType
    {
        return $this->type;
    }

    private static function validateStoredName(string $storedName, ReceiptDocumentType $type): string
    {
        if (
            self::STORED_NAME_MAX_LENGTH < strlen($storedName)
            || 1 !== preg_match(self::STORED_NAME_PATTERN, $storedName)
            || !str_ends_with($storedName, sprintf('.%s', $type->value))
        ) {
            throw InvalidStockReceiptData::becauseOfStoredDocumentName();
        }

        return $storedName;
    }

    private static function normalizeOriginalName(string $originalName): string
    {
        $basename = basename(str_replace('\\', '/', trim($originalName)));
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $basename);

        if (
            null === $normalized
            || '' === $normalized
            || self::ORIGINAL_NAME_MAX_LENGTH < mb_strlen($normalized)
        ) {
            throw InvalidStockReceiptData::becauseOfDocumentName();
        }

        return $normalized;
    }
}
