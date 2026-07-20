<?php

declare(strict_types=1);

namespace App\Stock\Application\Port;

use App\Stock\Application\Dto\ReceiptDocumentUpload;
use App\Stock\Application\Dto\StoredReceiptDocument;

interface ReceiptDocumentStorage
{
    public function store(ReceiptDocumentUpload $upload): StoredReceiptDocument;

    public function delete(string $storedName): void;
}
