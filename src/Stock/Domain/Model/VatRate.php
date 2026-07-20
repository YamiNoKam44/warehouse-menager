<?php

declare(strict_types=1);

namespace App\Stock\Domain\Model;

enum VatRate: int
{
    case ZERO = 0;
    case REDUCED_FIVE = 5;
    case REDUCED_EIGHT = 8;
    case STANDARD = 23;
}
