<?php

declare(strict_types=1);

namespace App\Tests\Unit\Article\Domain;

use App\Article\Domain\Exception\InvalidArticleData;
use App\Article\Domain\Model\Article;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    public function testItCreatesArticleAndNormalizesWhitespace(): void
    {
        $article = Article::create('  Taśma   pakowa  ', ' szt. ');

        self::assertNull($article->id());
        self::assertSame('Taśma pakowa', $article->name());
        self::assertSame('szt.', $article->unitOfMeasure());
    }

    public function testItUpdatesArticle(): void
    {
        $article = Article::restore(7, 'Taśma pakowa', 'szt.');

        $article->update('Taśma wzmacniana', 'rolka');

        self::assertSame(7, $article->id());
        self::assertSame('Taśma wzmacniana', $article->name());
        self::assertSame('rolka', $article->unitOfMeasure());
    }

    public function testItRejectsNameOutsideDefinedLength(): void
    {
        $this->expectException(InvalidArticleData::class);

        Article::create('A', 'szt.');
    }

    public function testItRejectsUnitOfMeasureOutsideDefinedLength(): void
    {
        $this->expectException(InvalidArticleData::class);

        Article::create('Taśma', str_repeat('a', Article::UNIT_OF_MEASURE_MAX_LENGTH + 1));
    }
}
