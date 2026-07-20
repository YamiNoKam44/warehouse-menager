<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidLogin;

final class User
{
    public const int LOGIN_MIN_LENGTH = 3;
    public const int LOGIN_MAX_LENGTH = 180;
    public const int PASSWORD_HASH_MAX_LENGTH = 255;

    public const string LOGIN_PATTERN = '/\A[a-z0-9][a-z0-9._-]*\z/';

    private ?int $id;
    private string $login;
    private string $passwordHash;
    private UserRole $role;

    private function __construct(?int $id, string $login, string $passwordHash, UserRole $role)
    {
        if (null !== $id && $id < 1) {
            throw new \InvalidArgumentException('Identyfikator użytkownika musi być dodatni.');
        }

        $this->id = $id;
        $this->login = self::normalizeLogin($login);
        $this->passwordHash = self::validatePasswordHash($passwordHash);
        $this->role = $role;
    }

    public static function register(
        string $login,
        string $passwordHash,
        UserRole $role = UserRole::USER,
    ): self {
        return new self(null, $login, $passwordHash, $role);
    }

    public static function restore(int $id, string $login, string $passwordHash, UserRole $role): self
    {
        return new self($id, $login, $passwordHash, $role);
    }

    public static function normalizeLogin(string $login): string
    {
        $normalized = strtolower(trim($login));
        $length = strlen($normalized);

        if (self::LOGIN_MIN_LENGTH > $length
            || self::LOGIN_MAX_LENGTH < $length
            || 1 !== preg_match(self::LOGIN_PATTERN, $normalized))
        {
            throw InvalidLogin::fromString($login);
        }

        return $normalized;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function login(): string
    {
        return $this->login;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function rename(string $login): void
    {
        $this->login = self::normalizeLogin($login);
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = self::validatePasswordHash($passwordHash);
    }

    private static function validatePasswordHash(string $passwordHash): string
    {
        if ('' === trim($passwordHash) || self::PASSWORD_HASH_MAX_LENGTH < strlen($passwordHash)) {
            throw new \InvalidArgumentException('Hash hasła jest nieprawidłowy.');
        }

        return $passwordHash;
    }
}
