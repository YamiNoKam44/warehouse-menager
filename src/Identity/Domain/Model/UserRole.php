<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
}

