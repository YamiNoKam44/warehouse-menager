<?php

declare(strict_types=1);

namespace App\Identity\Application\Service;

use App\Identity\Application\Dto\AssignedWarehouseIds;
use App\Identity\Application\Dto\AssignedWarehouses;
use App\Identity\Application\Dto\UserData;
use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Application\Exception\UserNotFound;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Exception\PersistenceUniqueConstraintViolation;
use App\Shared\Application\Port\UnitOfWork;
use App\Warehouse\Domain\Repository\WarehouseRepository;

final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private WarehouseRepository $warehouses,
        private UnitOfWork $unitOfWork,
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
        $this->commit($user);

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
        $this->commit($user);

        return $user;
    }

    private function synchronizeWarehouses(User $user, AssignedWarehouseIds $assignedWarehouseIds): void
    {
        $userId = $user->id();
        $currentAssignedWarehouseIds = null === $userId
            ? AssignedWarehouseIds::none()
            : AssignedWarehouseIds::fromWarehouses(
                $this->warehouses->assignedToUser($userId),
            );
        $warehouseIdsToSynchronize = $assignedWarehouseIds->mergedWith(
            $currentAssignedWarehouseIds,
        );
        $warehousesToSynchronize = AssignedWarehouses::fromFound(
            $warehouseIdsToSynchronize,
            $this->warehouses->findByIds($warehouseIdsToSynchronize),
        );

        foreach ($warehousesToSynchronize as $warehouse) {
            if ($assignedWarehouseIds->contains($warehouse)) {
                $warehouse->assignUser($user);
            } else {
                $warehouse->unassignUser($user);
            }

            $this->warehouses->save($warehouse);
        }
    }

    private function commit(User $user): void
    {
        try {
            $this->unitOfWork->commit();
        } catch (PersistenceUniqueConstraintViolation $exception) {
            throw UserAlreadyExists::withLogin($user->login(), $exception);
        }
    }
}
