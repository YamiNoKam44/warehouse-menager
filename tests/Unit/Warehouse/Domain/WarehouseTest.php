<?php

declare(strict_types=1);

namespace App\Tests\Unit\Warehouse\Domain;

use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Warehouse\Domain\Exception\InvalidWarehouseData;
use App\Warehouse\Domain\Model\Warehouse;
use PHPUnit\Framework\TestCase;

final class WarehouseTest extends TestCase
{
    public function testItCreatesWarehouseAndAssignsUsers(): void
    {
        $user = User::restore(5, 'operator', 'hash', UserRole::USER);

        $warehouse = Warehouse::create('  Magazyn   główny  ', [$user, $user]);

        self::assertNull($warehouse->id());
        self::assertSame('Magazyn główny', $warehouse->name());
        self::assertSame(1, $warehouse->assignedUserCount());
    }

    public function testItUpdatesNameAndAssignedUsers(): void
    {
        $firstUser = User::restore(5, 'operator', 'hash', UserRole::USER);
        $secondUser = User::restore(6, 'magazynier', 'hash', UserRole::USER);
        $warehouse = Warehouse::restore(2, 'Magazyn główny', [$firstUser]);

        $warehouse->update('Magazyn pomocniczy', [$secondUser]);

        self::assertSame('Magazyn pomocniczy', $warehouse->name());
        self::assertSame(1, $warehouse->assignedUserCount());

        foreach ($warehouse->assignedUsers() as $assignedUser) {
            self::assertSame('magazynier', $assignedUser->login());
        }
    }

    public function testItRejectsNameOutsideDefinedLength(): void
    {
        $this->expectException(InvalidWarehouseData::class);

        Warehouse::create('A', []);
    }
}
