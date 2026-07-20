<?php

declare(strict_types=1);

namespace App\Stock\Domain\Repository;

use App\Stock\Domain\Model\StockIssue;

interface StockIssueRepository
{
    public function save(StockIssue $issue): void;
}
