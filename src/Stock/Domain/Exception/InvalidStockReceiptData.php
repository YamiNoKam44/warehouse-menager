<?php

declare(strict_types=1);

namespace App\Stock\Domain\Exception;

final class InvalidStockReceiptData extends \DomainException
{
    public static function becauseOfUnitNetPrice(): self
    {
        return new self('Cena jednostkowa netto musi być liczbą nieujemną z maksymalnie 2 miejscami po przecinku.');
    }

    public static function becauseOfDocumentLimit(int $maximum): self
    {
        return new self(sprintf('Do przyjęcia można dołączyć maksymalnie %d pliki.', $maximum));
    }

    public static function becauseOfDocumentType(): self
    {
        return new self('Dozwolone są wyłącznie pliki PDF lub XML.');
    }

    public static function becauseOfDocumentName(): self
    {
        return new self('Nazwa załączonego pliku jest nieprawidłowa.');
    }

    public static function becauseOfStoredDocumentName(): self
    {
        return new self('Nazwa zapisanego dokumentu jest nieprawidłowa.');
    }
}
