<?php

declare(strict_types=1);

namespace App\Tests\Unit\Stock\Domain;

use App\Article\Domain\Model\Article;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Stock\Domain\Exception\InvalidStockReceiptData;
use App\Stock\Domain\Model\ReceiptDocumentType;
use App\Stock\Domain\Exception\InvalidStockQuantity;
use App\Stock\Domain\Model\StockReceipt;
use App\Stock\Domain\Model\VatRate;
use App\Warehouse\Domain\Model\Warehouse;
use PHPUnit\Framework\TestCase;

final class StockReceiptTest extends TestCase
{
    public function testItCreatesReceiptAndTakesUnitFromArticle(): void
    {
        $article = Article::restore(8, 'Cement', 'kg');
        $receipt = StockReceipt::create(
            Warehouse::restore(3, 'Magazyn główny'),
            $article,
            User::restore(5, 'operator', 'hash', UserRole::USER),
            '12,5',
            VatRate::STANDARD,
            '19,9',
            new \DateTimeImmutable('2026-07-19 12:00:00'),
        );

        $article->update('Cement', 'tona');

        self::assertSame('12.500', $receipt->quantity());
        self::assertSame('kg', $receipt->unitOfMeasure());
        self::assertSame(23, $receipt->vatRate());
        self::assertSame('19.90', $receipt->unitNetPrice());
        self::assertSame('2026-07-19 12:00:00', $receipt->createdAt()->format('Y-m-d H:i:s'));
    }

    public function testItAcceptsAtMostFourDocuments(): void
    {
        $receipt = $this->receipt();

        for ($number = 1; $number <= StockReceipt::MAX_DOCUMENTS; ++$number) {
            $receipt->attachDocument(
                sprintf('%s.pdf', str_repeat((string) $number, 32)),
                sprintf('faktura-%d.pdf', $number),
                ReceiptDocumentType::PDF,
            );
        }

        self::assertSame(StockReceipt::MAX_DOCUMENTS, $receipt->documentCount());

        $this->expectException(InvalidStockReceiptData::class);

        $receipt->attachDocument(
            sprintf('%s.xml', str_repeat('a', 32)),
            'faktura.xml',
            ReceiptDocumentType::XML,
        );
    }

    public function testItRejectsZeroQuantity(): void
    {
        $this->expectException(InvalidStockQuantity::class);

        StockReceipt::create(
            Warehouse::restore(3, 'Magazyn główny'),
            Article::restore(8, 'Cement', 'kg'),
            User::restore(5, 'operator', 'hash', UserRole::USER),
            '0',
            VatRate::STANDARD,
            '19.99',
            new \DateTimeImmutable(),
        );
    }

    private function receipt(): StockReceipt
    {
        return StockReceipt::create(
            Warehouse::restore(3, 'Magazyn główny'),
            Article::restore(8, 'Cement', 'kg'),
            User::restore(5, 'operator', 'hash', UserRole::USER),
            '1',
            VatRate::STANDARD,
            '1',
            new \DateTimeImmutable(),
        );
    }
}
