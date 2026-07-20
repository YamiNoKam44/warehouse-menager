<?php

declare(strict_types=1);

namespace App\Warehouse\Infrastructure\Http\Dto;

use Symfony\Component\Form\FormView;

final readonly class WarehouseFormViewData
{
    public function __construct(
        private string $heading,
        private string $submitLabel,
        private FormView $form,
    ) {
    }

    /** @return array{heading: string, submit_label: string, form: FormView} */
    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'submit_label' => $this->submitLabel,
            'form' => $this->form,
        ];
    }
}
