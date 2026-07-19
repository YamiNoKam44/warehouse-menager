<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Dto;

use App\Identity\Domain\Model\User;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;
use IteratorAggregate;

/** @implements IteratorAggregate<int, int> */
final readonly class AssignedUserIds implements IteratorAggregate
{
    /** @var list<int> */
    private array $values;

    /**
     * @param iterable<int|string> $values
     */
    private function __construct(iterable $values)
    {
        $uniqueIds = [];

        foreach ($values as $value) {
            if ((!is_int($value) && !ctype_digit($value)) || 1 > (int) $value) {
                throw InvalidWarehouseData::becauseOfInvalidUserId();
            }

            $uniqueIds[(int) $value] = (int) $value;
        }

        $this->values = array_values($uniqueIds);
    }

    /**
     * @param iterable<int|string> $values
     */
    public static function fromInput(iterable $values): self
    {
        return new self($values);
    }

    /**
     * @param iterable<User> $users
     */
    public static function fromUsers(iterable $users): self
    {
        $ids = [];

        foreach ($users as $user) {
            if (null !== $user->id()) {
                $ids[] = $user->id();
            }
        }

        return new self($ids);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->values;
    }
}
