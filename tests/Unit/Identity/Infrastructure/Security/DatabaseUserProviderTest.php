<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Security;

use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Infrastructure\Security\DatabaseUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class DatabaseUserProviderTest extends TestCase
{
    public function testItLoadsUserFromRepositoryAndMapsItForSymfony(): void
    {
        $users = new ProviderUserRepository(
            User::restore(7, 'operator', 'password-hash', UserRole::USER),
        );

        $securityUser = (new DatabaseUserProvider($users))->loadUserByIdentifier('operator');

        self::assertSame(7, $securityUser->id());
        self::assertSame('operator', $securityUser->getUserIdentifier());
        self::assertSame('password-hash', $securityUser->getPassword());
        self::assertSame([UserRole::USER->value], $securityUser->getRoles());
    }

    public function testItThrowsForUnknownUser(): void
    {
        $provider = new DatabaseUserProvider(new ProviderUserRepository());

        try {
            $provider->loadUserByIdentifier('missing');
            self::fail('Oczekiwano wyjątku UserNotFoundException.');
        } catch (UserNotFoundException $exception) {
            self::assertSame('missing', $exception->getUserIdentifier());
        }
    }
}

final class ProviderUserRepository implements UserRepository
{
    public function __construct(private ?User $user = null)
    {
    }

    public function findByLogin(string $login): ?User
    {
        return null !== $this->user && $this->user->login() === User::normalizeLogin($login)
            ? $this->user
            : null;
    }

    public function save(User $user): void
    {
        $this->user = $user;
    }
}
