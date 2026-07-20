<?php

declare(strict_types=1);

namespace App\Stock\Application\Service;

use App\Article\Application\Exception\ArticleNotFound;
use App\Article\Domain\Model\Article;
use App\Article\Domain\Repository\ArticleRepository;
use App\Identity\Application\Exception\UserNotFound;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Stock\Application\Dto\IssueData;
use App\Stock\Application\Exception\StockIssueAccessDenied;
use App\Stock\Domain\Model\StockIssue;
use App\Stock\Domain\Repository\StockIssueRepository;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;

final readonly class StockIssueService
{
    public function __construct(
        private UserRepository $users,
        private WarehouseRepository $warehouses,
        private ArticleRepository $articles,
        private StockIssueRepository $issues,
    ) {
    }

    /** @return iterable<Warehouse> */
    public function availableWarehouses(int $userId): iterable
    {
        $user = $this->findUser($userId);

        if (UserRole::ADMIN === $user->role()) {
            return $this->warehouses->all();
        }

        return $this->warehouses->assignedToUser($userId);
    }

    /** @return iterable<Article> */
    public function availableArticles(): iterable
    {
        return $this->articles->all();
    }

    public function issue(int $userId, IssueData $data): void
    {
        $user = $this->findUser($userId);
        $warehouse = $this->warehouses->find($data->warehouseId)
            ?? throw WarehouseNotFound::withId($data->warehouseId);

        if (UserRole::ADMIN !== $user->role() && !$warehouse->isAssignedTo($user)) {
            throw StockIssueAccessDenied::toWarehouse();
        }

        $article = $this->articles->find($data->articleId)
            ?? throw ArticleNotFound::withId($data->articleId);

        $this->issues->save(StockIssue::create(
            $warehouse,
            $article,
            $user,
            $data->quantity,
            new \DateTimeImmutable(),
        ));
    }

    private function findUser(int $userId): User
    {
        return $this->users->find($userId) ?? throw UserNotFound::withId($userId);
    }
}
