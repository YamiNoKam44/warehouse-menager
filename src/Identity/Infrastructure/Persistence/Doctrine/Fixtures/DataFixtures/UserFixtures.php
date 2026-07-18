<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine\Fixtures\DataFixtures;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Model\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    private const string USER_LOGIN = 'operator';
    private const string ADMIN_LOGIN = 'admin';
    private const string PASSWORD = 'warehouse123';

    public function __construct(private readonly PasswordHasher $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $manager->persist(User::register(
            self::USER_LOGIN,
            $this->passwordHasher->hash(self::PASSWORD),
        ));

        $manager->persist(User::register(
            self::ADMIN_LOGIN,
            $this->passwordHasher->hash(self::PASSWORD),
            UserRole::ADMIN,
        ));

        $manager->flush();
    }
}
