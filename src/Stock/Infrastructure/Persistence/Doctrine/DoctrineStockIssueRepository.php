<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Persistence\Doctrine;

use App\Stock\Domain\Model\StockIssue;
use App\Stock\Domain\Repository\StockIssueRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineStockIssueRepository implements StockIssueRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(StockIssue $issue): void
    {
        $this->entityManager->persist($issue);
    }
}
