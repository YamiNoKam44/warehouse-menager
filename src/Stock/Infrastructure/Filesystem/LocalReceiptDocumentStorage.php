<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Filesystem;

use App\Stock\Application\Dto\ReceiptDocumentUpload;
use App\Stock\Application\Dto\StoredReceiptDocument;
use App\Stock\Application\Exception\CannotStoreReceiptDocument;
use App\Stock\Application\Port\ReceiptDocumentStorage;

final readonly class LocalReceiptDocumentStorage implements ReceiptDocumentStorage
{
    private const int DIRECTORY_PERMISSIONS = 0700;
    private const int FILE_PERMISSIONS = 0600;
    private const int RANDOM_NAME_BYTES = 16;

    public function __construct(private string $directory)
    {
    }

    public function store(ReceiptDocumentUpload $upload): StoredReceiptDocument
    {
        if (!is_file($upload->temporaryPath) || !is_readable($upload->temporaryPath)) {
            throw CannotStoreReceiptDocument::create();
        }

        $this->ensureDirectoryExists();

        try {
            do {
                $storedName = sprintf(
                    '%s.%s',
                    bin2hex(random_bytes(self::RANDOM_NAME_BYTES)),
                    $upload->type->value,
                );
                $targetPath = $this->targetPath($storedName);
            } while (file_exists($targetPath));
        } catch (\Throwable $exception) {
            throw CannotStoreReceiptDocument::create($exception);
        }

        if (!@copy($upload->temporaryPath, $targetPath)) {
            throw CannotStoreReceiptDocument::create();
        }

        if (!@chmod($targetPath, self::FILE_PERMISSIONS)) {
            @unlink($targetPath);

            throw CannotStoreReceiptDocument::create();
        }

        return new StoredReceiptDocument(
            $storedName,
            $upload->originalName,
            $upload->type,
        );
    }

    public function delete(string $storedName): void
    {
        if ($storedName !== basename($storedName)) {
            return;
        }

        $path = $this->targetPath($storedName);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (!@mkdir($this->directory, self::DIRECTORY_PERMISSIONS, true) && !is_dir($this->directory)) {
            throw CannotStoreReceiptDocument::create();
        }
    }

    private function targetPath(string $storedName): string
    {
        return sprintf(
            '%s%s%s',
            rtrim($this->directory, '/\\'),
            DIRECTORY_SEPARATOR,
            $storedName,
        );
    }
}
