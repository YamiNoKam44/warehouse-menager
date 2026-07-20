<?php

declare(strict_types=1);

namespace App\Stock\Infrastructure\Http\Dto;

use Symfony\Component\Form\FormView;

final readonly class StockOperationFormViewData
{
    public function __construct(
        private FormView $form,
        private bool $saved,
    ) {
    }

    /** @return array{form: FormView, saved: bool} */
    public function toArray(): array
    {
        return [
            'form' => $this->form,
            'saved' => $this->saved,
        ];
    }
}
