<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain;

use App\Identity\Domain\Exception\InvalidLogin;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testItNormalizesLoginAndAssignsUserRole(): void
    {
        $user = User::register('  Jan.Kowalski  ', 'password-hash');

        self::assertSame('jan.kowalski', $user->login());
        self::assertSame(UserRole::USER, $user->role());
        self::assertNull($user->id());
    }

    public function testItRestoresAdministratorWithStableId(): void
    {
        $user = User::restore(42, 'admin', 'password-hash', UserRole::ADMIN);

        self::assertSame(42, $user->id());
        self::assertSame(UserRole::ADMIN, $user->role());
    }

    public function testItRejectsInvalidLogin(): void
    {
        $this->expectException(InvalidLogin::class);

        User::register('nie poprawny login', 'password-hash');
    }
}

