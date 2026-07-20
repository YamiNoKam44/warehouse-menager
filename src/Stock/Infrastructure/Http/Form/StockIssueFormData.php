<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http\Form;

use App\Article\Domain\Model\Article;
use App\Stock\Application\Dto\IssueData;
use App\Warehouse\Domain\Model\Warehouse;

final class StockIssueFormData
{
    public function __construct(
        private ?Warehouse $warehouse = null,
        private ?Article $article = null,
        private string $quantity = '',
    ) {
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): void
    {
        $this->article = $article;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function toIssueData(): IssueData
    {
        $warehouseId = $this->warehouse?->id();
        $articleId = $this->article?->id();

        if (null === $warehouseId || null === $articleId) {
            throw new \LogicException('Magazyn i artykuł muszą być zapisane przed wydaniem.');
        }

        return new IssueData($warehouseId, $articleId, $this->quantity);
    }
}
