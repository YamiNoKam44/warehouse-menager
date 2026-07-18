<?php

declare(strict_types=1);

namespace App\Article\Domain\Model;

use App\Article\Domain\Exception\InvalidArticleData;

final class Article
{
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 160;
    public const int UNIT_OF_MEASURE_MIN_LENGTH = 1;
    public const int UNIT_OF_MEASURE_MAX_LENGTH = 32;

    private ?int $id;
    private string $name;
    private string $unitOfMeasure;

    private function __construct(?int $id, string $name, string $unitOfMeasure)
    {
        if (null !== $id && $id < 1) {
            throw new \InvalidArgumentException('Identyfikator artykułu musi być dodatni.');
        }

        $this->id = $id;
        $this->name = self::normalizeName($name);
        $this->unitOfMeasure = self::normalizeUnitOfMeasure($unitOfMeasure);
    }

    public static function create(string $name, string $unitOfMeasure): self
    {
        return new self(null, $name, $unitOfMeasure);
    }

    public static function restore(int $id, string $name, string $unitOfMeasure): self
    {
        return new self($id, $name, $unitOfMeasure);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unitOfMeasure(): string
    {
        return $this->unitOfMeasure;
    }

    public function update(string $name, string $unitOfMeasure): void
    {
        $this->name = self::normalizeName($name);
        $this->unitOfMeasure = self::normalizeUnitOfMeasure($unitOfMeasure);
    }

    private static function normalizeName(string $name): string
    {
        $normalized = self::normalizeWhitespace($name);
        $length = mb_strlen($normalized);

        if (self::NAME_MIN_LENGTH > $length || self::NAME_MAX_LENGTH < $length) {
            throw InvalidArticleData::becauseOfNameLength(
                self::NAME_MIN_LENGTH,
                self::NAME_MAX_LENGTH,
            );
        }

        return $normalized;
    }

    private static function normalizeUnitOfMeasure(string $unitOfMeasure): string
    {
        $normalized = self::normalizeWhitespace($unitOfMeasure);
        $length = mb_strlen($normalized);

        if (
            self::UNIT_OF_MEASURE_MIN_LENGTH > $length
            || self::UNIT_OF_MEASURE_MAX_LENGTH < $length
        ) {
            throw InvalidArticleData::becauseOfUnitOfMeasureLength(
                self::UNIT_OF_MEASURE_MIN_LENGTH,
                self::UNIT_OF_MEASURE_MAX_LENGTH,
            );
        }

        return $normalized;
    }

    private static function normalizeWhitespace(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        if (null === $normalized) {
            throw new \InvalidArgumentException('Wartość zawiera nieprawidłowe znaki.');
        }

        return $normalized;
    }
}
