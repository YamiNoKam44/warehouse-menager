<?php

declare(strict_types=1);

namespace App\Tests\Integration\Warehouse\Infrastructure\Persistence;

use App\Identity\Application\Dto\AssignedWarehouseIds;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Port\UnitOfWork;
use App\Warehouse\Domain\Model\Warehouse;
use App\Warehouse\Infrastructure\Persistence\Doctrine\DoctrineWarehouseRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineWarehouseRepositoryTest extends KernelTestCase
{
    private Connection $connection;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()->get(Connection::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $databaseName = (string) $this->connection->getDatabase();

        if (!str_ends_with($databaseName, '_test')) {
            throw new \LogicException(sprintf('Testy nie mogą modyfikować bazy "%s".', $databaseName));
        }

        $this->clearDatabase();
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->clearDatabase();
        }

        parent::tearDown();
    }

    public function testLightQueryDoesNotLoadUsersButBatchQueryDoes(): void
    {
        $users = self::getContainer()->get(UserRepository::class);
        $warehouses = self::getContainer()->get(DoctrineWarehouseRepository::class);
        $unitOfWork = self::getContainer()->get(UnitOfWork::class);

        $operator = User::register('operator', 'password-hash');
        $manager = User::register('manager', 'password-hash');
        $users->save($operator);
        $users->save($manager);

        $warehouse = Warehouse::create('Magazyn główny', [$operator, $manager]);
        $warehouses->save($warehouse);
        $unitOfWork->commit();

        $operatorId = (int) $operator->id();
        $managerId = (int) $manager->id();
        $this->entityManager->clear();

        $lightResults = iterator_to_array($warehouses->assignedToUser($operatorId));

        self::assertCount(1, $lightResults);
        $lightUsers = $lightResults[0]->assignedUsers();
        self::assertInstanceOf(PersistentCollection::class, $lightUsers);
        self::assertFalse($lightUsers->isInitialized());

        $warehouseIds = AssignedWarehouseIds::fromWarehouses($lightResults);
        $updateResults = iterator_to_array($warehouses->findByIds($warehouseIds));

        self::assertCount(1, $updateResults);
        $updateUsers = $updateResults[0]->assignedUsers();
        self::assertInstanceOf(PersistentCollection::class, $updateUsers);
        self::assertTrue($updateUsers->isInitialized());
        self::assertCount(2, iterator_to_array($updateUsers));
        $managedWarehouse = $updateResults[0];
        $operatorReference = $this->entityManager->getReference(User::class, $operatorId);
        $managerReference = $this->entityManager->getReference(User::class, $managerId);
        $missingUserReference = $this->entityManager->getReference(User::class, PHP_INT_MAX);

        self::assertTrue(
            $warehouses->isUserAssignedTo($managedWarehouse, $operatorReference),
        );
        self::assertTrue(
            $warehouses->isUserAssignedTo($managedWarehouse, $managerReference),
        );
        self::assertFalse(
            $warehouses->isUserAssignedTo($managedWarehouse, $missingUserReference),
        );
    }

    private function clearDatabase(): void
    {
        $this->connection->executeStatement('DELETE FROM warehouse_users');
        $this->connection->executeStatement('DELETE FROM warehouses');
        $this->connection->executeStatement('DELETE FROM identity_users');
    }
}
