<?php

declare(strict_types=1);

namespace App\Warehouse\Application\Service;

use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Exception\PersistenceForeignKeyConstraintViolation;
use App\Shared\Application\Port\UnitOfWork;
use App\Warehouse\Application\Dto\AssignedUserIds;
use App\Warehouse\Application\Dto\AssignedUsers;
use App\Warehouse\Application\Dto\WarehouseData;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Exception\WarehouseInUse;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;

final readonly class WarehouseService
{
    public function __construct(
        private WarehouseRepository $warehouses,
        private UserRepository $users,
        private UnitOfWork $unitOfWork,
    ) {
    }

    public function create(WarehouseData $data): Warehouse
    {
        $warehouse = Warehouse::create(
            $data->name,
            $this->assignedUsers($data->assignedUserIds),
        );

        $this->warehouses->save($warehouse);
        $this->unitOfWork->commit();

        return $warehouse;
    }

    public function update(int $id, WarehouseData $data): Warehouse
    {
        $warehouse = $this->find($id);
        $warehouse->update(
            $data->name,
            $this->assignedUsers($data->assignedUserIds),
        );
        $this->warehouses->save($warehouse);
        $this->unitOfWork->commit();

        return $warehouse;
    }

    public function delete(int $id): void
    {
        try {
            $this->warehouses->remove($this->find($id));
            $this->unitOfWork->commit();
        } catch (PersistenceForeignKeyConstraintViolation $exception) {
            throw WarehouseInUse::create($exception);
        }
    }

    private function find(int $id): Warehouse
    {
        return $this->warehouses->find($id) ?? throw WarehouseNotFound::withId($id);
    }

    private function assignedUsers(AssignedUserIds $userIds): AssignedUsers
    {
        return AssignedUsers::fromFound($userIds, $this->users->findByIds($userIds));
    }
}
