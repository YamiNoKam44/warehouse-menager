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
use App\Stock\Application\Dto\ReceiptData;
use App\Stock\Application\Exception\StockReceiptAccessDenied;
use App\Stock\Application\Port\ReceiptDocumentStorage;
use App\Stock\Domain\Model\StockReceipt;
use App\Stock\Domain\Repository\StockReceiptRepository;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;

final readonly class StockReceiptService
{
    public function __construct(
        private UserRepository $users,
        private WarehouseRepository $warehouses,
        private ArticleRepository $articles,
        private StockReceiptRepository $receipts,
        private ReceiptDocumentStorage $documentStorage,
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

    public function receive(int $userId, ReceiptData $data): void
    {
        $user = $this->findUser($userId);
        $warehouse = $this->warehouses->find($data->warehouseId)
            ?? throw WarehouseNotFound::withId($data->warehouseId);

        if (UserRole::ADMIN !== $user->role() && !$warehouse->isAssignedTo($user)) {
            throw StockReceiptAccessDenied::toWarehouse();
        }

        $article = $this->articles->find($data->articleId)
            ?? throw ArticleNotFound::withId($data->articleId);

        $receipt = StockReceipt::create(
            $warehouse,
            $article,
            $user,
            $data->quantity,
            $data->vatRate,
            $data->unitNetPrice,
            new \DateTimeImmutable(),
        );

        try {
            foreach ($data->documents as $document) {
                $storedDocument = $this->documentStorage->store($document);

                try {
                    $receipt->attachDocument(
                        $storedDocument->storedName,
                        $storedDocument->originalName,
                        $storedDocument->type,
                    );
                } catch (\Throwable $exception) {
                    $this->documentStorage->delete($storedDocument->storedName);

                    throw $exception;
                }
            }

            $this->receipts->save($receipt);
        } catch (\Throwable $exception) {
            foreach ($receipt->documents() as $document) {
                $this->documentStorage->delete($document->storedName());
            }

            throw $exception;
        }
    }

    private function findUser(int $userId): User
    {
        return $this->users->find($userId) ?? throw UserNotFound::withId($userId);
    }
}
