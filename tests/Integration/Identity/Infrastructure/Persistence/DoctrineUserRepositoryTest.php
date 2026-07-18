<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity\Infrastructure\Persistence;

use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\User;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()->get(Connection::class);
        $databaseName = (string) $this->connection->getDatabase();

        if (!str_ends_with($databaseName, '_test')) {
            throw new \LogicException(sprintf('Testy nie mog? modyfikowa? bazy "%s".', $databaseName));
        }

        $this->connection->executeStatement('DELETE FROM identity_users');
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DELETE FROM identity_users');
        }

        parent::tearDown();
    }

    public function testItTranslatesDuplicateLoginConstraint(): void
    {
        $repository = self::getContainer()->get(DoctrineUserRepository::class);
        $repository->save(User::register('operator', 'first-hash'));

        $this->expectException(UserAlreadyExists::class);

        $repository->save(User::register('operator', 'second-hash'));
    }
}

