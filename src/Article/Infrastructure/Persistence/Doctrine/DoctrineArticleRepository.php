<?php

declare(strict_types=1);

namespace App\Article\Infrastructure\Persistence\Doctrine;

use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineArticleRepository implements ArticleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function all(): iterable
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('article')
            ->from(Article::class, 'article')
            ->orderBy('article.name', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    public function find(int $id): ?Article
    {
        $article = $this->entityManager->find(Article::class, $id);

        return $article instanceof Article ? $article : null;
    }

    public function save(Article $article): void
    {
        $this->entityManager->persist($article);
    }

    public function remove(Article $article): void
    {
        $this->entityManager->remove($article);
    }
}
