<?php

declare(strict_types=1);

namespace App\Article\Application\Service;

use App\Article\Application\Dto\ArticleData;
use App\Article\Application\Exception\ArticleNotFound;
use App\Article\Domain\Exception\ArticleInUse;
use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use App\Shared\Application\Exception\PersistenceForeignKeyConstraintViolation;
use App\Shared\Application\Port\UnitOfWork;

final readonly class ArticleService
{
    public function __construct(
        private ArticleRepository $articles,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function create(ArticleData $data): Article
    {
        $article = Article::create($data->name, $data->unitOfMeasure);

        $this->articles->save($article);
        $this->unitOfWork->commit();

        return $article;
    }

    public function update(int $id, ArticleData $data): Article
    {
        $article = $this->find($id);
        $article->update($data->name, $data->unitOfMeasure);
        $this->articles->save($article);
        $this->unitOfWork->commit();

        return $article;
    }

    public function delete(int $id): void
    {
        try {
            $this->articles->remove($this->find($id));
            $this->unitOfWork->commit();
        } catch (PersistenceForeignKeyConstraintViolation $exception) {
            throw ArticleInUse::create($exception);
        }
    }

    private function find(int $id): Article
    {
        return $this->articles->find($id) ?? throw ArticleNotFound::withId($id);
    }
}
