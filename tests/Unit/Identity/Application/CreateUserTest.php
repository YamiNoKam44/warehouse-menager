<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\CreateUser;
use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Exception\PersistenceUniqueConstraintViolation;
use App\Shared\Application\Port\UnitOfWork;
use App\Tests\Support\InMemoryUnitOfWork;
use PHPUnit\Framework\TestCase;

final class CreateUserTest extends TestCase
{
    public function testItCreatesAndPersistsUser(): void
    {
        $users = new InMemoryUserRepository();
        $unitOfWork = new InMemoryUnitOfWork();
        $createUser = new CreateUser($users, new FakePasswordHasher(), $unitOfWork);

        $user = $createUser->execute('  Operator.01 ', 'bezpieczne-haslo');

        self::assertSame('operator.01', $user->login());
        self::assertSame('hashed:bezpieczne-haslo', $user->passwordHash());
        self::assertSame($user, $users->saved);
        self::assertSame(1, $unitOfWork->commitCount());
    }

    public function testItRejectsShortPassword(): void
    {
        $createUser = new CreateUser(
            new InMemoryUserRepository(),
            new FakePasswordHasher(),
            new InMemoryUnitOfWork(),
        );

        $this->expectException(InvalidPassword::class);

        $createUser->execute('operator', str_repeat('a', CreateUser::PASSWORD_MIN_LENGTH - 1));
    }

    public function testItTranslatesDuplicateLoginConflict(): void
    {
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork
            ->method('commit')
            ->willThrowException(PersistenceUniqueConstraintViolation::fromPrevious(
                new \RuntimeException(),
            ));

        $this->expectException(UserAlreadyExists::class);

        (new CreateUser(
            new InMemoryUserRepository(),
            new FakePasswordHasher(),
            $unitOfWork,
        ))->execute('operator', 'bezpieczne-haslo');
    }
}

final class InMemoryUserRepository implements UserRepository
{
    public ?User $saved = null;

    public function all(): iterable
    {
        return new \EmptyIterator();
    }

    public function findByIds(iterable $ids): iterable
    {
        return new \EmptyIterator();
    }

    public function find(int $id): ?User
    {
        return null;
    }

    public function findByLogin(string $login): ?User
    {
        return null;
    }

    public function save(User $user): void
    {
        $this->saved = $user;
    }
}

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return sprintf('hashed:%s', $plainPassword);
    }
}

