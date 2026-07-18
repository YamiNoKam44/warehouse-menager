<?php

declare(strict_types=1);

namespace App\Article\Domain\Repository;

use App\Article\Domain\Model\Article;

interface ArticleRepository
{
    /**
     * @return iterable<Article>
     */
    public function all(): iterable;

    public function find(int $id): ?Article;

    public function save(Article $article): void;

    public function remove(Article $article): void;
}
