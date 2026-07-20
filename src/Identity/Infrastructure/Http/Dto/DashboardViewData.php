<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Dto;

final readonly class DashboardViewData
{
    public function __construct(private string $login)
    {
    }

    /** @return array{login: string} */
    public function toArray(): array
    {
        return ['login' => $this->login];
    }
}
