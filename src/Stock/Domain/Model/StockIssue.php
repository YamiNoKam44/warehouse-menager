<?php

declare(strict_types=1);

namespace App\Stock\Domain\Model;

use App\Article\Domain\Model\Article;
use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Model\Warehouse;

final class StockIssue
{
    private ?int $id;
    private string $quantity;
    private string $unitOfMeasure;

    private function __construct(
        private readonly Warehouse $warehouse,
        private readonly Article $article,
        private readonly User $issuedBy,
        string $quantity,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->id = null;
        $this->quantity = StockQuantity::fromInput($quantity)->value();
        $this->unitOfMeasure = $article->unitOfMeasure();
    }

    public static function create(
        Warehouse $warehouse,
        Article $article,
        User $issuedBy,
        string $quantity,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($warehouse, $article, $issuedBy, $quantity, $createdAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function warehouse(): Warehouse
    {
        return $this->warehouse;
    }

    public function article(): Article
    {
        return $this->article;
    }

    public function issuedBy(): User
    {
        return $this->issuedBy;
    }

    public function quantity(): string
    {
        return $this->quantity;
    }

    public function unitOfMeasure(): string
    {
        return $this->unitOfMeasure;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
