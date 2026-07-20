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
use App\Shared\Application\Port\UnitOfWork;
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
        private UnitOfWork $unitOfWork,
    ) {
    }

    /** @return iterable<Warehouse> */
    public function availableWarehouses(int $userId): iterable
    {
        $user = $this->findUser($userId);

        if ($this->isAdmin($user)) {
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
        $warehouse = $this->findWarehouse($data->warehouseId);
        $this->assertWarehouseAccess($user, $warehouse);
        $article = $this->findArticle($data->articleId);

        $issue = StockIssue::create(
            $warehouse,
            $article,
            $user,
            $data->quantity,
            new \DateTimeImmutable(),
        );

        $this->issues->save($issue);
        $this->unitOfWork->commit();
    }

    private function findUser(int $userId): User
    {
        $user = $this->users->find($userId);

        return $user ?? throw UserNotFound::withId($userId);
    }

    private function findWarehouse(int $warehouseId): Warehouse
    {
        $warehouse = $this->warehouses->find($warehouseId);

        return $warehouse ?? throw WarehouseNotFound::withId($warehouseId);
    }

    private function findArticle(int $articleId): Article
    {
        $article = $this->articles->find($articleId);

        return $article ?? throw ArticleNotFound::withId($articleId);
    }

    private function assertWarehouseAccess(User $user, Warehouse $warehouse): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        $isAssigned = $this->warehouses->isUserAssignedTo($warehouse, $user);

        if (!$isAssigned) {
            throw StockIssueAccessDenied::toWarehouse();
        }
    }

    private function isAdmin(User $user): bool
    {
        return UserRole::ADMIN === $user->role();
    }
}
