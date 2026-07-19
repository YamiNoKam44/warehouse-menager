<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Service;

use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Warehouse\Application\Dto\AssignedUserIds;
use App\Warehouse\Application\Dto\WarehouseData;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;

final readonly class WarehouseService
{
    public function __construct(
        private WarehouseRepository $warehouses,
        private UserRepository $users,
    ) {
    }

    public function create(WarehouseData $data): Warehouse
    {
        $warehouse = Warehouse::create(
            $data->name,
            $this->usersFrom($data->assignedUserIds),
        );

        $this->warehouses->save($warehouse);

        return $warehouse;
    }

    public function update(int $id, WarehouseData $data): Warehouse
    {
        $warehouse = $this->find($id);
        $warehouse->update(
            $data->name,
            $this->usersFrom($data->assignedUserIds),
        );
        $this->warehouses->save($warehouse);

        return $warehouse;
    }

    public function delete(int $id): void
    {
        $this->warehouses->remove($this->find($id));
    }

    private function find(int $id): Warehouse
    {
        return $this->warehouses->find($id) ?? throw WarehouseNotFound::withId($id);
    }

    /** @return iterable<User> */
    private function usersFrom(AssignedUserIds $userIds): iterable
    {
        foreach ($userIds as $userId) {
            $user = $this->users->find($userId);

            if (null === $user) {
                throw InvalidWarehouseData::becauseUserDoesNotExist($userId);
            }

            yield $user;
        }
    }
}
