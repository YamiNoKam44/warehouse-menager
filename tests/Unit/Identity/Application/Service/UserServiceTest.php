<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Service;

use App\Identity\Application\Dto\AssignedWarehouseIds;
use App\Identity\Application\Dto\PlainPassword;
use App\Identity\Application\Dto\UserData;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Application\Service\UserService;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Port\UnitOfWork;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testItLoadsAssignmentsInOneBatchAndCommitsOnce(): void
    {
        $mainWarehouse = Warehouse::restore(3, 'Magazyn główny');
        $auxiliaryWarehouse = Warehouse::restore(7, 'Magazyn pomocniczy');
        $assignedWarehouseIds = AssignedWarehouseIds::fromWarehouses([
            $mainWarehouse,
            $auxiliaryWarehouse,
        ]);

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(User::class));

        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::once())
            ->method('findByIds')
            ->with($assignedWarehouseIds)
            ->willReturn(new \ArrayIterator([$mainWarehouse, $auxiliaryWarehouse]));
        $warehouses->expects(self::never())->method('assignedToUser');
        $warehouses->expects(self::exactly(2))->method('save');

        $passwordHasher = $this->createMock(PasswordHasher::class);
        $passwordHasher->expects(self::once())
            ->method('hash')
            ->with('bezpieczne-haslo')
            ->willReturn('password-hash');

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())->method('commit');

        $user = (new UserService(
            $users,
            $passwordHasher,
            $warehouses,
            $unitOfWork,
        ))->create(new UserData(
            'operator',
            PlainPassword::fromString('bezpieczne-haslo'),
            $assignedWarehouseIds,
        ));

        self::assertSame('operator', $user->login());
        self::assertTrue($mainWarehouse->isAssignedTo($user));
        self::assertTrue($auxiliaryWarehouse->isAssignedTo($user));
    }

    public function testUpdateUsesFullAssignmentsOnlyForSynchronization(): void
    {
        $user = User::restore(11, 'operator', 'password-hash', UserRole::USER);
        $currentWarehouse = Warehouse::restore(3, 'Current warehouse', [$user]);
        $newWarehouse = Warehouse::restore(7, 'New warehouse');
        $assignedWarehouseIds = AssignedWarehouseIds::fromWarehouses([$newWarehouse]);

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())
            ->method('find')
            ->with(11)
            ->willReturn($user);
        $users->expects(self::once())
            ->method('save')
            ->with($user);

        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::once())
            ->method('assignedToUser')
            ->with(11)
            ->willReturn(new \ArrayIterator([$currentWarehouse]));
        $warehouses->expects(self::once())
            ->method('findByIds')
            ->with(self::callback(
                static fn (AssignedWarehouseIds $ids): bool => [7, 3] === iterator_to_array($ids),
            ))
            ->willReturn(new \ArrayIterator([$newWarehouse, $currentWarehouse]));
        $warehouses->expects(self::exactly(2))->method('save');

        $passwordHasher = $this->createMock(PasswordHasher::class);
        $passwordHasher->expects(self::never())->method('hash');

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())->method('commit');

        (new UserService(
            $users,
            $passwordHasher,
            $warehouses,
            $unitOfWork,
        ))->update(11, new UserData(
            'operator2',
            null,
            $assignedWarehouseIds,
        ));

        self::assertSame('operator2', $user->login());
        self::assertFalse($currentWarehouse->isAssignedTo($user));
        self::assertTrue($newWarehouse->isAssignedTo($user));
    }
}
