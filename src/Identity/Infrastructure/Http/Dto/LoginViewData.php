<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Dto;

final readonly class LoginViewData
{
    public function __construct(
        private string $lastUsername,
        private bool $authenticationFailed,
    ) {
    }

    /** @return array{last_username: string, authentication_failed: bool} */
    public function toArray(): array
    {
        return [
            'last_username' => $this->lastUsername,
            'authentication_failed' => $this->authenticationFailed,
        ];
    }
}
