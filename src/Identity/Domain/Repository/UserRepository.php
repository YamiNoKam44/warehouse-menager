<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Model\User;

interface UserRepository
{
    public function find(int $id): ?User;

    public function findByLogin(string $login): ?User;

    public function save(User $user): void;
}
