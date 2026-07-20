<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Dto;

use Symfony\Component\Form\FormView;

final readonly class UserFormViewData
{
    public function __construct(
        private string $heading,
        private string $submitLabel,
        private bool $passwordRequired,
        private FormView $form,
    ) {
    }

    /**
     * @return array{
     *     heading: string,
     *     submit_label: string,
     *     password_required: bool,
     *     form: FormView
     * }
     */
    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'submit_label' => $this->submitLabel,
            'password_required' => $this->passwordRequired,
            'form' => $this->form,
        ];
    }
}
