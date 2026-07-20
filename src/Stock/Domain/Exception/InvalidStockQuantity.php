<?php

declare(strict_types=1);

namespace App\Stock\Domain\Exception;

final class InvalidStockQuantity extends \DomainException
{
    public static function create(): self
    {
        return new self('Ilość musi być dodatnią liczbą z maksymalnie 3 miejscami po przecinku.');
    }
}
