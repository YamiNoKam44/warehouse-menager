<?php

declare(strict_types=1);

namespace App\Stock\Domain\Model;

use App\Stock\Domain\Exception\InvalidStockQuantity;

final readonly class StockQuantity
{
    public const int PRECISION = 15;
    public const int SCALE = 3;
    public const string INPUT_PATTERN = '/\A\d{1,12}(?:[.,]\d{1,3})?\z/';

    private const string ZERO = '0.000';

    private function __construct(private string $value)
    {
    }

    public static function fromInput(string $quantity): self
    {
        $normalized = str_replace(',', '.', trim($quantity));

        if (1 !== preg_match(self::INPUT_PATTERN, $normalized)) {
            throw InvalidStockQuantity::create();
        }

        $parts = explode('.', $normalized, 2);
        $integerPart = ltrim($parts[0], '0');
        $integerPart = '' === $integerPart ? '0' : $integerPart;
        $fractionPart = str_pad($parts[1] ?? '', self::SCALE, '0');
        $value = $integerPart.'.'.$fractionPart;

        if (self::ZERO === $value) {
            throw InvalidStockQuantity::create();
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
