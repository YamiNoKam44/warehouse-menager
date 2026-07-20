<?php

declare(strict_types=1);

namespace App\Article\Infrastructure\Http\Dto;

use App\Article\Domain\Model\Article;

final readonly class ArticleIndexViewData
{
    /** @param iterable<Article> $articles */
    public function __construct(private iterable $articles)
    {
    }

    /** @return array{articles: iterable<Article>} */
    public function toArray(): array
    {
        return ['articles' => $this->articles];
    }
}
