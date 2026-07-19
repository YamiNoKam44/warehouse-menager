<?php

declare(strict_types=1);

namespace App\Warehouse\Domain\Model;

use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;

final class Warehouse
{
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 160;

    private ?int $id;
    private string $name;

    /** @var iterable<User> */
    private iterable $users;

    /**
     * @param iterable<User> $users
     */
    private function __construct(?int $id, string $name, iterable $users)
    {
        if (null !== $id && $id < 1) {
            throw new \InvalidArgumentException('Identyfikator magazynu musi być dodatni.');
        }

        $this->id = $id;
        $this->name = self::normalizeName($name);
        $this->replaceUsers($users);
    }

    /**
     * @param iterable<User> $users
     */
    public static function create(string $name, iterable $users): self
    {
        return new self(null, $name, $users);
    }

    /**
     * @param iterable<User> $users
     */
    public static function restore(int $id, string $name, iterable $users = []): self
    {
        return new self($id, $name, $users);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return iterable<User>
     */
    public function assignedUsers(): iterable
    {
        return $this->users;
    }

    public function assignedUserCount(): int
    {
        $count = 0;

        foreach ($this->users as $user) {
            ++$count;
        }

        return $count;
    }

    /**
     * @param iterable<User> $users
     */
    public function update(string $name, iterable $users): void
    {
        $this->name = self::normalizeName($name);
        $this->replaceUsers($users);
    }

    /**
     * @param iterable<User> $users
     */
    private function replaceUsers(iterable $users): void
    {
        $assignedUsers = [];

        foreach ($users as $user) {
            if (!in_array($user, $assignedUsers, true)) {
                $assignedUsers[] = $user;
            }
        }

        $this->users = $assignedUsers;
    }

    private static function normalizeName(string $name): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($name));

        if (null === $normalized) {
            throw new \InvalidArgumentException('Nazwa zawiera nieprawidłowe znaki.');
        }

        $length = mb_strlen($normalized);

        if (self::NAME_MIN_LENGTH > $length || self::NAME_MAX_LENGTH < $length) {
            throw InvalidWarehouseData::becauseOfNameLength(
                self::NAME_MIN_LENGTH,
                self::NAME_MAX_LENGTH,
            );
        }

        return $normalized;
    }
}
