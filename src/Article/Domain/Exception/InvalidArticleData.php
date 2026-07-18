<?php

declare(strict_types=1);

namespace App\Article\Domain\Exception;

final class InvalidArticleData extends \DomainException
{
    public static function becauseOfNameLength(int $minimumLength, int $maximumLength): self
    {
        return new self(sprintf(
            'Nazwa artykułu musi mieć od %d do %d znaków.',
            $minimumLength,
            $maximumLength,
        ));
    }

    public static function becauseOfUnitOfMeasureLength(int $minimumLength, int $maximumLength): self
    {
        return new self(sprintf(
            'Jednostka miary musi mieć od %d do %d znaków.',
            $minimumLength,
            $maximumLength,
        ));
    }
}
