<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Model\User;

interface UserRepository
{
    /** @return iterable<User> */
    public function all(): iterable;

    /**
     * @param iterable<int> $ids
     *
     * @return iterable<User>
     */
    public function findByIds(iterable $ids): iterable;

    public function find(int $id): ?User;

    public function findByLogin(string $login): ?User;

    public function save(User $user): void;
}
