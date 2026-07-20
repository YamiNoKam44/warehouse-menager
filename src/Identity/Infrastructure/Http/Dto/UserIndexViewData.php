<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http\Dto;

use App\Identity\Domain\Model\User;

final readonly class UserIndexViewData
{
    /** @param iterable<User> $users */
    public function __construct(private iterable $users)
    {
    }

    /** @return array{users: iterable<User>} */
    public function toArray(): array
    {
        return ['users' => $this->users];
    }
}
