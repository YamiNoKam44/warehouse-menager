<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Persistence\Doctrine;

use App\Stock\Domain\Model\StockReceipt;
use App\Stock\Domain\Repository\StockReceiptRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineStockReceiptRepository implements StockReceiptRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(StockReceipt $receipt): void
    {
        $this->entityManager->persist($receipt);
    }
}
