<?php

declare(strict_types=1);

namespace App\Article\Application\Dto;

use App\Article\Domain\Model\Article;

final readonly class ArticleData
{
    public function __construct(
        public string $name,
        public string $unitOfMeasure,
    ) {
    }

    public static function fromArticle(Article $article): self
    {
        return new self($article->name(), $article->unitOfMeasure());
    }
}
