<?php

declare(strict_types=1);

namespace App\Article\Infrastructure\Http\Dto;

use App\Article\Application\Dto\ArticleData;

final readonly class ArticleFormViewData
{
    public function __construct(
        private string $heading,
        private string $submitLabel,
        private string $csrfTokenId,
        private ArticleData $data,
        private ?string $error,
    ) {
    }

    /**
     * @return array{
     *     heading: string,
     *     submit_label: string,
     *     csrf_token_id: string,
     *     data: ArticleData,
     *     error: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'submit_label' => $this->submitLabel,
            'csrf_token_id' => $this->csrfTokenId,
            'data' => $this->data,
            'error' => $this->error,
        ];
    }
}
