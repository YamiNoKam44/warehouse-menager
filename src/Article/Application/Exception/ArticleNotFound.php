<?php

declare(strict_types=1);

namespace App\Article\Application\Exception;

final class ArticleNotFound extends \DomainException
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Artykuł o identyfikatorze %d nie istnieje.', $id));
    }
}
