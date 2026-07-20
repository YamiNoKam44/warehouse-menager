<?php

declare(strict_types=1);

namespace App\Tests\Unit\Article\Application\Service;

use App\Article\Application\Service\ArticleService;
use App\Article\Application\Dto\ArticleData;
use App\Article\Application\Exception\ArticleNotFound;
use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use PHPUnit\Framework\TestCase;

final class ArticleServiceTest extends TestCase
{
    public function testItCreatesAndSavesArticle(): void
    {
        $repository = $this->createMock(ArticleRepository::class);
        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn (Article $article): bool =>
                'Taśma pakowa' === $article->name()
                && 'szt.' === $article->unitOfMeasure()
            ));

        $article = (new ArticleService($repository))->create(
            new ArticleData('Taśma pakowa', 'szt.'),
        );

        self::assertSame('Taśma pakowa', $article->name());
    }

    public function testItUpdatesAndSavesExistingArticle(): void
    {
        $article = Article::restore(8, 'Taśma pakowa', 'szt.');
        $repository = $this->createMock(ArticleRepository::class);
        $repository->expects(self::once())->method('find')->with(8)->willReturn($article);
        $repository->expects(self::once())->method('save')->with($article);

        (new ArticleService($repository))->update(
            8,
            new ArticleData('Taśma wzmacniana', 'rolka'),
        );

        self::assertSame('Taśma wzmacniana', $article->name());
        self::assertSame('rolka', $article->unitOfMeasure());
    }

    public function testItCannotUpdateMissingArticle(): void
    {
        $repository = $this->createMock(ArticleRepository::class);
        $repository->expects(self::once())->method('find')->with(99)->willReturn(null);
        $repository->expects(self::never())->method('save');

        $this->expectException(ArticleNotFound::class);

        (new ArticleService($repository))->update(
            99,
            new ArticleData('Taśma', 'szt.'),
        );
    }

    public function testItDeletesExistingArticle(): void
    {
        $article = Article::restore(11, 'Taśma pakowa', 'szt.');
        $repository = $this->createMock(ArticleRepository::class);
        $repository->expects(self::once())->method('find')->with(11)->willReturn($article);
        $repository->expects(self::once())->method('remove')->with($article);

        (new ArticleService($repository))->delete(11);
    }

    public function testItCannotDeleteMissingArticle(): void
    {
        $repository = $this->createMock(ArticleRepository::class);
        $repository->expects(self::once())->method('find')->with(99)->willReturn(null);
        $repository->expects(self::never())->method('remove');

        $this->expectException(ArticleNotFound::class);

        (new ArticleService($repository))->delete(99);
    }
}
