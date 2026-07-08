<?php

declare(strict_types=1);

namespace App\Enums;

enum UserTier: string
{
    case Access   = 'access';
    case Practice = 'practice';

    public function label(): string
    {
        return match ($this) {
            self::Access   => 'Access',
            self::Practice => 'Practice',
        };
    }

    public function monthlyCents(): int
    {
        return match ($this) {
            self::Access   => 2900,   // $29/mo (corrected from stale $19)
            self::Practice => 4900,
        };
    }
}
