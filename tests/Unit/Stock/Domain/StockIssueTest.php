<?php

declare(strict_types=1);

namespace App\Tests\Unit\Stock\Domain;

use App\Article\Domain\Model\Article;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Stock\Domain\Model\StockIssue;
use App\Warehouse\Domain\Model\Warehouse;
use PHPUnit\Framework\TestCase;

final class StockIssueTest extends TestCase
{
    public function testItCreatesIssueAndTakesUnitFromArticle(): void
    {
        $article = Article::restore(8, 'Cement', 'kg');
        $issue = StockIssue::create(
            Warehouse::restore(3, 'Magazyn główny'),
            $article,
            User::restore(5, 'operator', 'hash', UserRole::USER),
            '2,5',
            new \DateTimeImmutable('2026-07-19 12:00:00'),
        );

        $article->update('Cement', 'tona');

        self::assertSame('2.500', $issue->quantity());
        self::assertSame('kg', $issue->unitOfMeasure());
        self::assertSame('2026-07-19 12:00:00', $issue->createdAt()->format('Y-m-d H:i:s'));
    }
}
