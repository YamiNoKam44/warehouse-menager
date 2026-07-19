<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

use App\Identity\Application\Exception\InvalidPassword;

final readonly class PlainPassword
{
    public const int MIN_LENGTH = 8;
    public const int MAX_LENGTH = 4096;

    private function __construct(#[\SensitiveParameter] private string $value)
    {
    }

    public static function fromString(#[\SensitiveParameter] string $value): self
    {
        $length = strlen($value);

        if (self::MIN_LENGTH > $length || self::MAX_LENGTH < $length) {
            throw InvalidPassword::becauseOfLength(self::MIN_LENGTH, self::MAX_LENGTH);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
