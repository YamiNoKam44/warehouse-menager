<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Console;

use App\Identity\Application\CreateUser;
use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Infrastructure\Console\CreateUserCommand;
use App\Tests\Support\InMemoryUnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends TestCase
{
    public function testItCreatesUserUsingHiddenPasswordPrompts(): void
    {
        $users = new ConsoleUserRepository();
        $createUser = new CreateUser(
            $users,
            new ConsolePasswordHasher(),
            new InMemoryUnitOfWork(),
        );
        $tester = new CommandTester(new CreateUserCommand($createUser));
        $tester->setInputs(['bezpieczne-haslo', 'bezpieczne-haslo']);

        $exitCode = $tester->execute(['login' => 'Operator']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame('operator', $users->saved?->login());
        self::assertStringContainsString('operator', $tester->getDisplay());
    }

    public function testItRejectsDifferentPasswordConfirmation(): void
    {
        $users = new ConsoleUserRepository();
        $createUser = new CreateUser(
            $users,
            new ConsolePasswordHasher(),
            new InMemoryUnitOfWork(),
        );
        $tester = new CommandTester(new CreateUserCommand($createUser));
        $tester->setInputs(['bezpieczne-haslo', 'inne-bezpieczne-haslo']);

        $exitCode = $tester->execute(['login' => 'operator']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertNull($users->saved);
        self::assertStringContainsString('[ERROR]', $tester->getDisplay());
    }
}

final class ConsoleUserRepository implements UserRepository
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

final class ConsolePasswordHasher implements PasswordHasher
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return sprintf('hashed:%s', $plainPassword);
    }
}
