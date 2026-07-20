<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http\Form;

use App\Article\Domain\Model\Article;
use App\Stock\Application\Dto\ReceiptData;
use App\Stock\Application\Dto\ReceiptDocumentUpload;
use App\Stock\Application\Dto\ReceiptDocumentUploads;
use App\Stock\Domain\Model\ReceiptDocumentType;
use App\Stock\Domain\Model\VatRate;
use App\Warehouse\Domain\Model\Warehouse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class StockReceiptFormData
{
    /** @var Collection<int, UploadedFile> */
    private Collection $documents;

    public function __construct(
        private ?Warehouse $warehouse = null,
        private ?Article $article = null,
        private string $quantity = '',
        private ?VatRate $vatRate = null,
        private string $unitNetPrice = '',
    ) {
        $this->documents = new ArrayCollection();
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

    public function getVatRate(): ?VatRate
    {
        return $this->vatRate;
    }

    public function setVatRate(?VatRate $vatRate): void
    {
        $this->vatRate = $vatRate;
    }

    public function getUnitNetPrice(): string
    {
        return $this->unitNetPrice;
    }

    public function setUnitNetPrice(string $unitNetPrice): void
    {
        $this->unitNetPrice = $unitNetPrice;
    }

    /** @return Collection<int, UploadedFile> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /** @param iterable<UploadedFile> $documents */
    public function setDocuments(iterable $documents): void
    {
        $this->documents->clear();

        foreach ($documents as $document) {
            $this->documents->add($document);
        }
    }

    public function toReceiptData(): ReceiptData
    {
        $warehouseId = $this->warehouse?->id();
        $articleId = $this->article?->id();

        if (null === $warehouseId || null === $articleId) {
            throw new \LogicException('Magazyn i artykuł muszą być zapisane przed przyjęciem.');
        }

        if (null === $this->vatRate) {
            throw new \LogicException('Stawka VAT musi być wybrana przed przyjęciem.');
        }

        return new ReceiptData(
            $warehouseId,
            $articleId,
            $this->quantity,
            $this->vatRate,
            $this->unitNetPrice,
            ReceiptDocumentUploads::fromIterable($this->documentUploads()),
        );
    }

    /** @return iterable<ReceiptDocumentUpload> */
    private function documentUploads(): iterable
    {
        foreach ($this->documents as $document) {
            yield new ReceiptDocumentUpload(
                $document->getPathname(),
                $document->getClientOriginalName(),
                ReceiptDocumentType::fromExtension($document->getClientOriginalExtension()),
            );
        }
    }
}
