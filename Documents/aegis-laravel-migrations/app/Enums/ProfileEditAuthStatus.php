<?php

declare(strict_types=1);

namespace App\Enums;

enum ProfileEditAuthStatus: string
{
    case Active  = 'active';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active  => 'Active',
            self::Revoked => 'Revoked',
        };
    }

    public function color(): string
    {
        return $this === self::Active ? 'green' : 'gray';
    }
}
