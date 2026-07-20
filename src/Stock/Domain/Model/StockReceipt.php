<?php

declare(strict_types=1);

namespace App\Stock\Domain\Model;

use App\Article\Domain\Model\Article;
use App\Identity\Domain\Model\User;
use App\Stock\Domain\Exception\InvalidStockReceiptData;
use App\Warehouse\Domain\Model\Warehouse;

final class StockReceipt
{
    public const int UNIT_NET_PRICE_SCALE = 2;
    public const int MAX_DOCUMENTS = 4;

    public const string UNIT_NET_PRICE_INPUT_PATTERN = '/\A\d{1,13}(?:[.,]\d{1,2})?\z/';

    private ?int $id;
    private string $quantity;
    private string $unitOfMeasure;
    private int $vatRate;
    private string $unitNetPrice;

    /** @var iterable<ReceiptDocument> */
    private iterable $documents;

    private function __construct(
        private readonly Warehouse $warehouse,
        private readonly Article $article,
        private readonly User $receivedBy,
        string $quantity,
        VatRate $vatRate,
        string $unitNetPrice,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->id = null;
        $this->quantity = StockQuantity::fromInput($quantity)->value();
        $this->unitOfMeasure = $article->unitOfMeasure();
        $this->vatRate = $vatRate->value;
        $this->unitNetPrice = self::normalizeUnitNetPrice($unitNetPrice);
        $this->documents = [];
    }

    public static function create(
        Warehouse $warehouse,
        Article $article,
        User $receivedBy,
        string $quantity,
        VatRate $vatRate,
        string $unitNetPrice,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $warehouse,
            $article,
            $receivedBy,
            $quantity,
            $vatRate,
            $unitNetPrice,
            $createdAt,
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function warehouse(): Warehouse
    {
        return $this->warehouse;
    }

    public function article(): Article
    {
        return $this->article;
    }

    public function receivedBy(): User
    {
        return $this->receivedBy;
    }

    public function quantity(): string
    {
        return $this->quantity;
    }

    public function unitOfMeasure(): string
    {
        return $this->unitOfMeasure;
    }

    public function vatRate(): int
    {
        return $this->vatRate;
    }

    public function unitNetPrice(): string
    {
        return $this->unitNetPrice;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return iterable<ReceiptDocument> */
    public function documents(): iterable
    {
        return $this->documents;
    }

    public function documentCount(): int
    {
        $count = 0;

        foreach ($this->documents as $document) {
            ++$count;
        }

        return $count;
    }

    public function attachDocument(
        string $storedName,
        string $originalName,
        ReceiptDocumentType $type,
    ): void {
        if (self::MAX_DOCUMENTS <= $this->documentCount()) {
            throw InvalidStockReceiptData::becauseOfDocumentLimit(self::MAX_DOCUMENTS);
        }

        $documents = [];

        foreach ($this->documents as $document) {
            $documents[] = $document;
        }

        $documents[] = ReceiptDocument::create($this, $storedName, $originalName, $type);
        $this->documents = $documents;
    }

    private static function normalizeUnitNetPrice(string $unitNetPrice): string
    {
        return self::normalizeDecimal(
            $unitNetPrice,
            self::UNIT_NET_PRICE_INPUT_PATTERN,
            self::UNIT_NET_PRICE_SCALE,
        ) ?? throw InvalidStockReceiptData::becauseOfUnitNetPrice();
    }

    private static function normalizeDecimal(
        string $value,
        string $pattern,
        int $scale,
    ): ?string {
        $normalized = str_replace(',', '.', trim($value));

        if (1 !== preg_match($pattern, $normalized)) {
            return null;
        }

        $parts = explode('.', $normalized, 2);
        $integerPart = ltrim($parts[0], '0');
        $integerPart = '' === $integerPart ? '0' : $integerPart;
        $fractionPart = $parts[1] ?? '';
        $fractionPart = str_pad($fractionPart, $scale, '0');

        return $integerPart.'.'.$fractionPart;
    }
}
