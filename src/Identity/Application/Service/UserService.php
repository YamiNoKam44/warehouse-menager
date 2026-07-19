<?php

declare(strict_types=1);

namespace App\Identity\Application\Service;

use App\Identity\Application\Dto\AssignedWarehouseIds;
use App\Identity\Application\Dto\UserData;
use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Application\Exception\UserNotFound;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Domain\Repository\WarehouseRepository;

final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private WarehouseRepository $warehouses,
    ) {
    }

    public function create(UserData $data): User
    {
        if (null === $data->password) {
            throw InvalidPassword::becauseRequired();
        }

        $user = User::register(
            $data->login,
            $this->passwordHasher->hash($data->password->value()),
        );

        $this->users->save($user);
        $this->synchronizeWarehouses($user, $data->assignedWarehouseIds);

        return $user;
    }

    public function update(int $id, UserData $data): User
    {
        $user = $this->users->find($id) ?? throw UserNotFound::withId($id);

        $user->rename($data->login);

        if (null !== $data->password) {
            $user->changePasswordHash(
                $this->passwordHasher->hash($data->password->value()),
            );
        }

        $this->users->save($user);
        $this->synchronizeWarehouses($user, $data->assignedWarehouseIds);

        return $user;
    }

    private function synchronizeWarehouses(User $user, AssignedWarehouseIds $assignedWarehouseIds): void
    {
        $userId = $user->id();

        if (null === $userId) {
            throw new \LogicException('Nie można przypisać magazynów niezapisanemu użytkownikowi.');
        }

        foreach ($this->warehouses->assignedToUser($userId) as $warehouse) {
            $warehouseId = $warehouse->id();

            if (null !== $warehouseId && !$assignedWarehouseIds->contains($warehouseId)) {
                $warehouse->unassignUser($user);
                $this->warehouses->save($warehouse);
            }
        }

        foreach ($assignedWarehouseIds as $warehouseId) {
            $warehouse = $this->warehouses->find($warehouseId)
                ?? throw WarehouseNotFound::withId($warehouseId);

            $warehouse->assignUser($user);
            $this->warehouses->save($warehouse);
        }
    }
}
