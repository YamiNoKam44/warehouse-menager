<?php

declare(strict_types=1);

namespace App\Tests\Unit\Warehouse\Application;

use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Warehouse\Application\Dto\AssignedUserIds;
use App\Warehouse\Application\Dto\WarehouseData;
use App\Warehouse\Application\Exception\WarehouseNotFound;
use App\Warehouse\Application\Service\WarehouseService;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Domain\Repository\WarehouseRepository;
use PHPUnit\Framework\TestCase;

final class WarehouseServiceTest extends TestCase
{
    public function testItCreatesWarehouseWithAssignedUser(): void
    {
        $user = User::restore(4, 'operator', 'hash', UserRole::USER);
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(4)->willReturn($user);

        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn (Warehouse $warehouse): bool =>
                'Magazyn główny' === $warehouse->name()
                && 1 === $warehouse->assignedUserCount()
            ));

        $warehouse = (new WarehouseService($warehouses, $users))->create(
            new WarehouseData('Magazyn główny', AssignedUserIds::fromInput(['4'])),
        );

        self::assertSame('Magazyn główny', $warehouse->name());
    }

    public function testItUpdatesExistingWarehouse(): void
    {
        $user = User::restore(7, 'magazynier', 'hash', UserRole::USER);
        $warehouse = Warehouse::restore(3, 'Stara nazwa');
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(7)->willReturn($user);
        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::once())->method('find')->with(3)->willReturn($warehouse);
        $warehouses->expects(self::once())->method('save')->with($warehouse);

        (new WarehouseService($warehouses, $users))->update(
            3,
            new WarehouseData('Nowa nazwa', AssignedUserIds::fromInput([7])),
        );

        self::assertSame('Nowa nazwa', $warehouse->name());
        self::assertSame(1, $warehouse->assignedUserCount());
    }

    public function testItRejectsUnknownAssignedUser(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(99)->willReturn(null);
        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::never())->method('save');

        $this->expectException(InvalidWarehouseData::class);

        (new WarehouseService($warehouses, $users))->create(
            new WarehouseData('Magazyn', AssignedUserIds::fromInput([99])),
        );
    }

    public function testItDeletesExistingWarehouse(): void
    {
        $warehouse = Warehouse::restore(8, 'Magazyn');
        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::once())->method('find')->with(8)->willReturn($warehouse);
        $warehouses->expects(self::once())->method('remove')->with($warehouse);
        $users = $this->createStub(UserRepository::class);

        (new WarehouseService($warehouses, $users))->delete(8);
    }

    public function testItCannotDeleteMissingWarehouse(): void
    {
        $warehouses = $this->createMock(WarehouseRepository::class);
        $warehouses->expects(self::once())->method('find')->with(99)->willReturn(null);
        $warehouses->expects(self::never())->method('remove');
        $users = $this->createStub(UserRepository::class);

        $this->expectException(WarehouseNotFound::class);

        (new WarehouseService($warehouses, $users))->delete(99);
    }
}
