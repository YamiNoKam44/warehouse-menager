<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\CreateUser;
use App\Identity\Application\Exception\InvalidPassword;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

final class CreateUserTest extends TestCase
{
    public function testItCreatesAndPersistsUser(): void
    {
        $users = new InMemoryUserRepository();
        $createUser = new CreateUser($users, new FakePasswordHasher());

        $user = $createUser->execute('  Operator.01 ', 'bezpieczne-haslo');

        self::assertSame('operator.01', $user->login());
        self::assertSame('hashed:bezpieczne-haslo', $user->passwordHash());
        self::assertSame($user, $users->saved);
    }

    public function testItRejectsShortPassword(): void
    {
        $createUser = new CreateUser(new InMemoryUserRepository(), new FakePasswordHasher());

        $this->expectException(InvalidPassword::class);

        $createUser->execute('operator', str_repeat('a', CreateUser::PASSWORD_MIN_LENGTH - 1));
    }
}

final class InMemoryUserRepository implements UserRepository
{
    public ?User $saved = null;

    public function all(): iterable
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
        return 'hashed:'.$plainPassword;
    }
}

