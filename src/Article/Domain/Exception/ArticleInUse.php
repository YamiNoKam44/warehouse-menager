<?php

declare(strict_types=1);

namespace App\Article\Domain\Exception;

final class ArticleInUse extends \DomainException
{
    public static function create(?\Throwable $previous = null): self
    {
        return new self('Nie można usunąć artykułu użytego w operacji magazynowej.', previous: $previous);
    }
}
