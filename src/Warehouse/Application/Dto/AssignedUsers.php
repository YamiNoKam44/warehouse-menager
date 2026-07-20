<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Dto;

use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;

/** @implements \IteratorAggregate<int, User> */
final readonly class AssignedUsers implements \IteratorAggregate
{
    /** @param list<User> $users */
    private function __construct(private array $users)
    {
    }

    /** @param iterable<User> $users */
    public static function fromFound(AssignedUserIds $requestedIds, iterable $users): self
    {
        $usersById = [];

        foreach ($users as $user) {
            $id = $user->id();

            if (null !== $id) {
                $usersById[$id] = $user;
            }
        }

        $assignedUsers = [];

        foreach ($requestedIds as $id) {
            $assignedUsers[] = $usersById[$id]
                ?? throw InvalidWarehouseData::becauseUserDoesNotExist($id);
        }

        return new self($assignedUsers);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->users;
    }
}
